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

$hasColumn = function (string $table, string $column) use ($pdo): bool {
    $stmt = $pdo->prepare("
        SELECT 1 FROM information_schema.columns
        WHERE table_name = :t AND column_name = :c
    ");
    $stmt->execute(['t' => $table, 'c' => $column]);
    return (bool) $stmt->fetchColumn();
};

$pdo->beginTransaction();

try {
    // ── invoices: mark, document_type, series, totals ──────────────────────
    if (!$hasColumn('invoices', 'mark')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN mark VARCHAR(50)");
        $pdo->exec("CREATE UNIQUE INDEX invoices_mark_unique ON invoices (mark) WHERE mark IS NOT NULL");
        echo "Added invoices.mark (unique where not null).\n";
    }

    if (!$hasColumn('invoices', 'document_type')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN document_type VARCHAR(100)");
        echo "Added invoices.document_type.\n";
    }

    if (!$hasColumn('invoices', 'series')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN series VARCHAR(50)");
        echo "Added invoices.series.\n";
    }

    if (!$hasColumn('invoices', 'net_total')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN net_total DOUBLE PRECISION");
        echo "Added invoices.net_total.\n";
    }

    if (!$hasColumn('invoices', 'vat_total')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN vat_total DOUBLE PRECISION");
        echo "Added invoices.vat_total.\n";
    }

    if (!$hasColumn('invoices', 'gross_total')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN gross_total DOUBLE PRECISION");
        echo "Added invoices.gross_total.\n";
    }

    // ── suppliers.afm ──────────────────────────────────────────────────────
    if (!$hasColumn('suppliers', 'afm')) {
        $pdo->exec("ALTER TABLE suppliers ADD COLUMN afm VARCHAR(20)");
        echo "Added suppliers.afm.\n";

        // Backfill from existing details JSON
        $updated = $pdo->exec("
            UPDATE suppliers
            SET afm = details->>'afm'
            WHERE details->>'afm' IS NOT NULL AND details->>'afm' <> ''
        ");
        echo "Backfilled suppliers.afm for {$updated} row(s) from details JSON.\n";

        $pdo->exec("CREATE UNIQUE INDEX suppliers_afm_unique ON suppliers (afm) WHERE afm IS NOT NULL");
        echo "Created unique index on suppliers.afm.\n";
    }

    // ── invoice_entries: structured line fields ────────────────────────────
    if (!$hasColumn('invoice_entries', 'supplier_code')) {
        $pdo->exec("ALTER TABLE invoice_entries ADD COLUMN supplier_code VARCHAR(100)");
        echo "Added invoice_entries.supplier_code.\n";
        $n = $pdo->exec("
            UPDATE invoice_entries
            SET supplier_code = extras->>'supplier_code'
            WHERE extras->>'supplier_code' IS NOT NULL
        ");
        echo "Backfilled invoice_entries.supplier_code for {$n} row(s).\n";
    }

    if (!$hasColumn('invoice_entries', 'unit')) {
        $pdo->exec("ALTER TABLE invoice_entries ADD COLUMN unit VARCHAR(50)");
        echo "Added invoice_entries.unit.\n";
        $n = $pdo->exec("
            UPDATE invoice_entries
            SET unit = extras->>'unit'
            WHERE extras->>'unit' IS NOT NULL
        ");
        echo "Backfilled invoice_entries.unit for {$n} row(s).\n";
    }

    if (!$hasColumn('invoice_entries', 'vat_amount')) {
        $pdo->exec("ALTER TABLE invoice_entries ADD COLUMN vat_amount DOUBLE PRECISION");
        echo "Added invoice_entries.vat_amount.\n";
    }

    if (!$hasColumn('invoice_entries', 'vat_rate')) {
        $pdo->exec("ALTER TABLE invoice_entries ADD COLUMN vat_rate INTEGER");
        echo "Added invoice_entries.vat_rate.\n";
        $n = $pdo->exec("
            UPDATE invoice_entries
            SET vat_rate = (extras->>'vat_rate')::integer
            WHERE extras->>'vat_rate' IS NOT NULL
              AND (extras->>'vat_rate') ~ '^[0-9]+$'
        ");
        echo "Backfilled invoice_entries.vat_rate for {$n} row(s).\n";
    }

    if (!$hasColumn('invoice_entries', 'line_number')) {
        $pdo->exec("ALTER TABLE invoice_entries ADD COLUMN line_number INTEGER");
        echo "Added invoice_entries.line_number.\n";
    }

    $pdo->commit();
    echo "Migration completed successfully.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
