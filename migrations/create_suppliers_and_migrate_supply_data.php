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

echo "Connected to database '{$_ENV['DB_NAME']}'.\n";

// Guard: check if supplier_id column already exists on supplies (idempotency check)
$supplierIdExists = $pdo->query("
    SELECT column_name FROM information_schema.columns
    WHERE table_name = 'supplies' AND column_name = 'supplier_id'
")->fetchColumn();
if ($supplierIdExists) {
    die("Column 'supplier_id' already exists on 'supplies'. Migration may have already run.\n");
}

// Check if suppliers table exists (in any schema accessible via search_path)
$suppliersTableExists = $pdo->query("
    SELECT table_name FROM information_schema.tables
    WHERE table_name = 'suppliers' AND table_schema NOT IN ('pg_catalog', 'information_schema')
    LIMIT 1
")->fetchColumn();

$pdo->beginTransaction();

try {
    if ($suppliersTableExists) {
        echo "Table 'suppliers' already exists — skipping creation.\n";
    } else {
        // 1. Create suppliers table
        $pdo->exec("
            CREATE TABLE suppliers (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                telephone VARCHAR(255) DEFAULT NULL
            )
        ");
        echo "Created table 'suppliers'.\n";
    }

    // 2. Insert unique supplier names from custom_fields that don't already exist
    $rows = $pdo->query("
        SELECT DISTINCT custom_fields->>'supplier' AS supplier_name
        FROM supplies
        WHERE custom_fields->>'supplier' IS NOT NULL
          AND custom_fields->>'supplier' != ''
    ")->fetchAll(PDO::FETCH_COLUMN);

    // Fetch already-existing supplier names (case-sensitive)
    $existingNames = $pdo->query("SELECT name FROM suppliers")->fetchAll(PDO::FETCH_COLUMN);
    $existingNamesMap = array_flip($existingNames);

    $insert = $pdo->prepare("INSERT INTO suppliers (name) VALUES (:name)");
    $inserted = 0;
    $skipped = 0;
    foreach ($rows as $name) {
        if (isset($existingNamesMap[$name])) {
            $skipped++;
            continue;
        }
        $insert->execute([':name' => $name]);
        $inserted++;
    }
    echo "Inserted {$inserted} new supplier(s) (skipped {$skipped} already existing).\n";

    // 3. Add supplier_id FK column to supplies
    $pdo->exec("
        ALTER TABLE supplies
        ADD COLUMN supplier_id INTEGER REFERENCES suppliers(id) ON DELETE SET NULL
    ");
    echo "Added 'supplier_id' column to 'supplies'.\n";

    // 4. Set supplier_id on each supply
    $pdo->exec("
        UPDATE supplies
        SET supplier_id = (
            SELECT id FROM suppliers
            WHERE name = custom_fields->>'supplier'
        )
        WHERE custom_fields->>'supplier' IS NOT NULL
          AND custom_fields->>'supplier' != ''
    ");
    echo "Linked supplies to their suppliers.\n";

    // 5. Remove 'supplier' key from custom_fields JSON in PHP (only rows with non-null custom_fields)
    $supplyRows = $pdo->query("SELECT id, custom_fields FROM supplies WHERE custom_fields IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    $update = $pdo->prepare("UPDATE supplies SET custom_fields = :json WHERE id = :id");
    foreach ($supplyRows as $row) {
        $data = json_decode($row['custom_fields'], true) ?? [];
        unset($data['supplier']);
        $update->execute([':json' => json_encode($data), ':id' => $row['id']]);
    }
    echo "Removed 'supplier' key from custom_fields on " . count($supplyRows) . " supply row(s).\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (Throwable $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
