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
    WHERE table_name = 'supply_price_history'
")->fetchColumn();

if ($tableExists) {
    die("Table 'supply_price_history' already exists. Migration may have already run.\n");
}

$pdo->beginTransaction();

try {
    $pdo->exec("
        CREATE TABLE supply_price_history (
            id          SERIAL PRIMARY KEY,
            supply_id   INTEGER NOT NULL REFERENCES supplies(id) ON DELETE CASCADE,
            price       DOUBLE PRECISION NOT NULL,
            valid_from  TIMESTAMP(0) WITH TIME ZONE NOT NULL
        )
    ");
    echo "Created table 'supply_price_history'.\n";

    $pdo->exec("CREATE INDEX idx_supply_price_history_supply_valid ON supply_price_history (supply_id, valid_from)");
    echo "Created index on (supply_id, valid_from).\n";

    // Seed current prices as the initial history records
    $count = $pdo->exec("
        INSERT INTO supply_price_history (supply_id, price, valid_from)
        SELECT id, price, CURRENT_TIMESTAMP FROM supplies
    ");
    echo "Seeded {$count} initial price record(s) from current supply prices.\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
