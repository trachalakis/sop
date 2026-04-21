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

$tableExists = $pdo->query("
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_name = 'daily_role_slots'
")->fetchColumn();

if ($tableExists) {
    die("Table 'daily_role_slots' already exists. Migration may have already run.\n");
}

$pdo->beginTransaction();

try {
    $pdo->exec("
        CREATE TABLE daily_role_slots (
            id      SERIAL PRIMARY KEY,
            date    DATE NOT NULL,
            role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE
        )
    ");
    echo "Created table 'daily_role_slots'.\n";

    $pdo->exec("CREATE INDEX idx_daily_role_slots_date ON daily_role_slots (date)");
    echo "Created index on (date).\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
