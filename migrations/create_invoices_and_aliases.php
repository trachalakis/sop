#!/usr/bin/env php
<?php

declare(strict_types=1);

$envFile = __DIR__ . '/../app/.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found at {$envFile}\n");
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
}

$dsn = "pgsql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}";

try {
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "Connected to '{$_ENV['DB_NAME']}'.\n";

// Idempotency guard
$exists = $pdo->query("
    SELECT table_name FROM information_schema.tables
    WHERE table_name = 'invoices' AND table_schema NOT IN ('pg_catalog','information_schema')
")->fetchColumn();
if ($exists) {
    die("Table 'invoices' already exists. Migration may have already run.\n");
}

$pdo->exec("
    CREATE TABLE supply_aliases (
        id          SERIAL PRIMARY KEY,
        supply_id   INTEGER NOT NULL REFERENCES supplies(id) ON DELETE CASCADE,
        supplier_id INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE CASCADE,
        description VARCHAR(500) NOT NULL,
        UNIQUE (supplier_id, description)
    );

    CREATE TABLE invoices (
        id             SERIAL PRIMARY KEY,
        supplier_id    INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
        date           DATE NOT NULL,
        invoice_number VARCHAR(100),
        scanned_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );

    CREATE TABLE invoice_entries (
        id              SERIAL PRIMARY KEY,
        invoice_id      INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
        description     VARCHAR(500) NOT NULL,
        quantity        FLOAT NOT NULL,
        unit_price      FLOAT NOT NULL,
        extras          JSONB,
        supply_alias_id INTEGER REFERENCES supply_aliases(id) ON DELETE SET NULL
    );
");

echo "Done: created supply_aliases, invoices, invoice_entries.\n";
