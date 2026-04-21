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
    WHERE table_name = 'roles'
")->fetchColumn();

if ($tableExists) {
    die("Table 'roles' already exists. Migration may have already run.\n");
}

$pdo->beginTransaction();

try {
    $pdo->exec("
        CREATE TABLE roles (
            id    SERIAL PRIMARY KEY,
            name  VARCHAR(255) NOT NULL UNIQUE,
            label VARCHAR(255) NOT NULL
        )
    ");
    echo "Created table 'roles'.\n";

    // Seed with the existing hardcoded roles from UserRole enum
    $seedRoles = [
        ['webmaster',           'Webmaster'],
        ['warm_cuisine',        'Warm Cuisine'],
        ['cold_cuisine',        'Cold Cuisine'],
        ['prep',                'Prep'],
        ['cleaning',            'Cleaning'],
        ['waiter',              'Waiter'],
        ['reservations_manager','Reservations Manager'],
        ['assistant_waiter',    'Assistant Waiter'],
        ['bar_manager',         'Bar Manager'],
        ['bar_assistant',       'Bar Assistant'],
    ];

    $stmt = $pdo->prepare("INSERT INTO roles (name, label) VALUES (:name, :label)");
    foreach ($seedRoles as [$name, $label]) {
        $stmt->execute(['name' => $name, 'label' => $label]);
        echo "Seeded role: {$name}\n";
    }

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
