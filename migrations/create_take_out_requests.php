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
    WHERE table_name = 'take_out_requests'
")->fetchColumn();

if ($tableExists) {
    die("Table 'take_out_requests' already exists. Migration may have already run.\n");
}

$pdo->beginTransaction();

try {
    $pdo->exec("
        CREATE TABLE take_out_requests (
            id              SERIAL PRIMARY KEY,
            created_at      TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            token           VARCHAR(36) NOT NULL UNIQUE,
            customer_name   VARCHAR(100) NOT NULL,
            customer_phone  VARCHAR(20) NOT NULL,
            notes           TEXT NOT NULL DEFAULT '',
            status          VARCHAR(16) NOT NULL,
            eta_minutes     INTEGER,
            responded_at    TIMESTAMP(0) WITHOUT TIME ZONE,
            order_id        INTEGER REFERENCES orders(id) ON DELETE SET NULL
        )
    ");
    echo "Created table 'take_out_requests'.\n";

    $pdo->exec("CREATE INDEX idx_take_out_requests_status ON take_out_requests (status)");
    echo "Created index on (status).\n";

    $pdo->exec("
        CREATE TABLE take_out_request_entries (
            id              SERIAL PRIMARY KEY,
            request_id      INTEGER NOT NULL REFERENCES take_out_requests(id) ON DELETE CASCADE,
            menu_item_id    INTEGER NOT NULL REFERENCES menu_items(id),
            menu_item_price DOUBLE PRECISION NOT NULL,
            quantity        INTEGER NOT NULL
        )
    ");
    echo "Created table 'take_out_request_entries'.\n";

    $pdo->exec("
        CREATE TABLE take_out_request_entry_extras (
            id              SERIAL PRIMARY KEY,
            entry_id        INTEGER NOT NULL REFERENCES take_out_request_entries(id) ON DELETE CASCADE,
            name            VARCHAR(255) NOT NULL,
            price           DOUBLE PRECISION NOT NULL
        )
    ");
    echo "Created table 'take_out_request_entry_extras'.\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
