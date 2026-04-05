#!/usr/bin/env php
<?php

declare(strict_types=1);

$envFile = __DIR__ . '/../app/.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found at {$envFile}\n");
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
}

$host     = $_ENV['DB_HOST']     ?? '';
$username = $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';
$dbName   = $_ENV['DB_NAME']     ?? '';

$dsn = "pgsql:host={$host};dbname={$dbName}";

try {
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "Connected to database '{$dbName}'.\n";

$path = 'admin/print-jobs/create';
$roleToAdd = 'shopping_lists_manager';

$pdo->beginTransaction();

try {
    $existing = $pdo->prepare("SELECT id, allowed_roles FROM user_permissions WHERE path = :path");
    $existing->execute(['path' => $path]);
    $row = $existing->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $roles = array_filter(explode(',', $row['allowed_roles']));
        if (!in_array($roleToAdd, $roles, true)) {
            $roles[] = $roleToAdd;
            $pdo->prepare("UPDATE user_permissions SET allowed_roles = :roles WHERE id = :id")
                ->execute(['roles' => implode(',', $roles), 'id' => $row['id']]);
            echo "Added '{$roleToAdd}' to existing permission for '{$path}'.\n";
        } else {
            echo "Role '{$roleToAdd}' already present for '{$path}'. Nothing to do.\n";
        }
    } else {
        $pdo->prepare("INSERT INTO user_permissions (path, allowed_roles) VALUES (:path, :roles)")
            ->execute(['path' => $path, 'roles' => $roleToAdd]);
        echo "Inserted new permission for '{$path}' with role '{$roleToAdd}'.\n";
    }

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
