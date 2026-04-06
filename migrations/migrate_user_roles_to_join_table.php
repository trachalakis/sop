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

// Guard: exit if migration already ran
$tableExists = $pdo->query("
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_name = 'user_roles'
")->fetchColumn();

if ($tableExists) {
    die("Table 'user_roles' already exists. Migration may have already run.\n");
}

$pdo->beginTransaction();

try {
    // Step 1: Create user_roles join table
    $pdo->exec("
        CREATE TABLE user_roles (
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
            PRIMARY KEY (user_id, role_id)
        )
    ");
    echo "Created table 'user_roles'.\n";

    // Step 2: Read all users and their current roles column
    $users = $pdo->query("SELECT id, roles FROM users")->fetchAll(PDO::FETCH_ASSOC);

    // Step 3: Collect all unique role names across all users
    $allRoleNames = [];
    foreach ($users as $user) {
        if (empty($user['roles'])) {
            continue;
        }
        foreach (explode(',', $user['roles']) as $name) {
            $name = trim($name);
            if ($name !== '') {
                $allRoleNames[$name] = true;
            }
        }
    }

    // Fetch existing roles keyed by name => id
    $existingRoles = $pdo->query("SELECT name, id FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Auto-create any missing roles
    $insertRole = $pdo->prepare("INSERT INTO roles (name, label) VALUES (:name, :label) RETURNING id");
    foreach (array_keys($allRoleNames) as $name) {
        if (!isset($existingRoles[$name])) {
            $insertRole->execute(['name' => $name, 'label' => $name]);
            $existingRoles[$name] = $insertRole->fetchColumn();
            echo "Auto-created missing role: {$name}\n";
        }
    }

    // Step 4: Populate user_roles join table
    $insertUserRole = $pdo->prepare(
        "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id) ON CONFLICT DO NOTHING"
    );
    foreach ($users as $user) {
        if (empty($user['roles'])) {
            continue;
        }
        foreach (explode(',', $user['roles']) as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $insertUserRole->execute(['user_id' => $user['id'], 'role_id' => $existingRoles[$name]]);
        }
        echo "Migrated roles for user ID {$user['id']}.\n";
    }

    // Step 5: Drop the old roles column from users
    $pdo->exec("ALTER TABLE users DROP COLUMN roles");
    echo "Dropped column 'roles' from 'users'.\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
