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

$host     = $_ENV['DB_HOST'] ?? '';
$dbname   = $_ENV['DB_NAME'] ?? '';
$username = $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

$dsn = "pgsql:host={$host};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "Connected to database '{$dbname}'.\n";

$exists = $pdo->query("
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_name = 'menu_items' AND column_name = 'fiscal_department'
")->fetchColumn();

if ($exists) {
    die("Column 'fiscal_department' already exists on 'menu_items'. Migration may have already run.\n");
}

$pdo->beginTransaction();
try {
    $pdo->exec("ALTER TABLE menu_items ADD COLUMN fiscal_department INTEGER DEFAULT NULL");
    echo "Added column 'fiscal_department' to 'menu_items'.\n";
    $pdo->commit();
    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
