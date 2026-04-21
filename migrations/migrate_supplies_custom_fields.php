#!/usr/bin/env php
<?php

declare(strict_types=1);

// Load .env
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
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "Connected to database '{$dbName}'.\n";

// Check that the old column exists
$stmt = $pdo->query("
    SELECT column_name, data_type
    FROM information_schema.columns
    WHERE table_name = 'supplies'
      AND column_name IN ('__custom_fields', 'custom_fields')
    ORDER BY column_name
");
$existing = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if (!isset($existing['__custom_fields'])) {
    die("Column '__custom_fields' not found in supplies table. Nothing to migrate.\n");
}

if (isset($existing['custom_fields'])) {
    die("Column 'custom_fields' already exists. Migration may have already run.\n");
}

echo "Found '__custom_fields' ({$existing['__custom_fields']}). Starting migration...\n";

$pdo->beginTransaction();

try {
    // Add new json column
    $pdo->exec("ALTER TABLE supplies ADD COLUMN custom_fields JSON");
    echo "Added column 'custom_fields' (JSON).\n";

    // Read all rows with serialized data
    $rows = $pdo->query("SELECT id, __custom_fields FROM supplies")->fetchAll(PDO::FETCH_ASSOC);
    echo "Migrating " . count($rows) . " row(s)...\n";

    $update = $pdo->prepare("UPDATE supplies SET custom_fields = :json WHERE id = :id");

    foreach ($rows as $row) {
        $serialized = $row['__custom_fields'];
        $data = $serialized !== null ? unserialize($serialized) : [];
        if ($data === false) {
            throw new RuntimeException("Failed to unserialize row id={$row['id']}: {$serialized}");
        }
        $update->execute([
            ':json' => json_encode($data),
            ':id'   => $row['id'],
        ]);
    }

    echo "Copied and converted data to 'custom_fields'.\n";

    // Drop old column
    $pdo->exec("ALTER TABLE supplies DROP COLUMN __custom_fields");
    echo "Dropped column '__custom_fields'.\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
