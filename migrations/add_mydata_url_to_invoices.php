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

$dsn = "pgsql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}";

try {
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'] ?? '', $_ENV['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "Connected to database '{$_ENV['DB_NAME']}'.\n";

$columnExists = $pdo->query("
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'invoices' AND column_name = 'mydata_url'
")->fetchColumn();

if ($columnExists) {
    die("Column 'mydata_url' already exists on 'invoices'. Migration may have already run.\n");
}

$pdo->beginTransaction();

try {
    $pdo->exec("ALTER TABLE invoices ADD COLUMN mydata_url TEXT");
    echo "Added invoices.mydata_url.\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
