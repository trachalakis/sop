# Utility Printer Flag Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an `isUtilityPrinter` boolean field to the `Printer` entity and wire it through the admin CRUD, GraphQL, and shopping list app so shopping lists can only be printed to utility printers.

**Architecture:** Add the field to the entity with getter/setter, expose it in the admin forms and GraphQL type, then replace the shopping list's `receiptPrinters` computed property and print button block with a `utilityPrinters` equivalent. A standalone PDO migration script adds the DB column.

**Tech Stack:** PHP 8, Doctrine ORM 3, Twig 3, GraphQL-PHP, Vue 3, Bootstrap 5, PostgreSQL

---

### Task 1: Update Printer entity

**Files:**
- Modify: `src/Domain/Entities/Printer.php`

- [ ] **Step 1: Add the field, getter, and setter**

In `src/Domain/Entities/Printer.php`, add the new field after the `$printerType` property (line 33), and add the getter after `getPrinterAddress()` (line 68), and add the setter after `setPrinterAddress()` (line 80).

The full updated file:

```php
<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Enums\PrinterType;
use Domain\Repositories\PrintersRepository;

#[ORM\Entity(repositoryClass: PrintersRepository::class)]
#[ORM\Table(name: 'printers')]
class Printer
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'boolean', name: 'is_receipt_printer')]
    private bool $isReceiptPrinter;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'string', name: 'name', unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', name: 'printer_address')]
    private ?string $printerAddress;

    #[ORM\Column(type: 'string', enumType: PrinterType::class, name: 'printer_type')]
    private PrinterType $printerType;

    #[ORM\Column(type: 'boolean', name: 'is_utility_printer')]
    private bool $isUtilityPrinter = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function getIsReceiptPrinter(): bool
    {
        return $this->isReceiptPrinter;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrinterType(): string
    {
        return $this->printerType->value;
    }

    public function getPrinterAddress(): ?string
    {
        return $this->printerAddress;
    }

    public function getIsUtilityPrinter(): bool
    {
        return $this->isUtilityPrinter;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setHasReceiptPrinter(bool $hasReceiptPrinter): void
    {
        $this->hasReceiptPrinter = $hasReceiptPrinter;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setPrinterAddress(?string $printerAddress): void
    {
        $this->printerAddress = $printerAddress;
    }

    public function setPrinterType(PrinterType $printerType): void
    {
        $this->printerType = $printerType;
    }

    public function setIsUtilityPrinter(bool $isUtilityPrinter): void
    {
        $this->isUtilityPrinter = $isUtilityPrinter;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Domain/Entities/Printer.php
git commit -m "feat: add isUtilityPrinter field to Printer entity"
```

---

### Task 2: Update admin CRUD actions and templates

**Files:**
- Modify: `src/Application/Actions/Admin/CreatePrinter.php`
- Modify: `src/Application/Actions/Admin/UpdatePrinter.php`
- Modify: `src/templates/admin/create_printer.twig`
- Modify: `src/templates/admin/update_printer.twig`
- Modify: `src/templates/admin/printers.twig`

- [ ] **Step 1: Update CreatePrinter.php**

In `src/Application/Actions/Admin/CreatePrinter.php`, add one line after `$printer->setPrinterAddress('0.0.0.0');` (line 30):

```php
$printer->setIsUtilityPrinter(boolval($requestData['isUtilityPrinter'] ?? false));
```

The updated POST block becomes:

```php
$printer = new Printer;
$printer->setIsActive(boolval($requestData['isActive']));
$printer->setName($requestData['name']);
$printer->setPrinterType(PrinterType::from($requestData['printerType']));
$printer->setHasReceiptPrinter(false);
$printer->setPrinterAddress('0.0.0.0');
$printer->setIsUtilityPrinter(boolval($requestData['isUtilityPrinter'] ?? false));
```

- [ ] **Step 2: Update UpdatePrinter.php**

In `src/Application/Actions/Admin/UpdatePrinter.php`, add one line after `$printer->setPrinterType(...)` (line 30):

```php
$printer->setIsUtilityPrinter(boolval($requestData['isUtilityPrinter'] ?? false));
```

The updated POST block becomes:

```php
$printer->setIsActive(boolval($requestData['isActive']));
$printer->setName($requestData['name']);
$printer->setPrinterType(PrinterType::from($requestData['printerType']));
$printer->setIsUtilityPrinter(boolval($requestData['isUtilityPrinter'] ?? false));
```

- [ ] **Step 3: Update create_printer.twig**

In `src/templates/admin/create_printer.twig`, add the new field block before the `<hr/>` (line 53):

```twig
<div class="row mb-3">
    <label class="col-sm-2 col-form-label">Utility Printer</label>
    <div class="col-sm-10">
        <select class="form-select" name="isUtilityPrinter">
            <option value="1">Ναί</option>
            <option value="0" selected>Όχι</option>
        </select>
    </div>
</div>
```

- [ ] **Step 4: Update update_printer.twig**

In `src/templates/admin/update_printer.twig`, add the new field block before the `<hr/>` (line 58), pre-filled from the printer object:

```twig
<div class="row mb-3">
    <label class="col-sm-2 col-form-label">Utility Printer</label>
    <div class="col-sm-10">
        <select class="form-select" name="isUtilityPrinter">
            <option value="1" {{printer.getIsUtilityPrinter ? 'selected' : ''}}>Ναί</option>
            <option value="0" {{printer.getIsUtilityPrinter ? '' : 'selected'}}>Όχι</option>
        </select>
    </div>
</div>
```

- [ ] **Step 5: Update printers.twig**

In `src/templates/admin/printers.twig`, add a "utility" badge alongside the existing "ανενεργός" badge (after line 31):

```twig
{% if printer.getIsUtilityPrinter %}
    <span class="badge rounded-pill text-bg-info">utility</span>
{% endif %}
```

The full `<td>` block becomes:

```twig
<td>
    <a class="text-decoration-none" href="/admin/printers/update?id={{printer.getId}}">{{printer.getName}}</a>
    {% if not printer.getIsActive %}
        <span class="badge rounded-pill text-bg-danger">ανενεργός</span>
    {% endif %}
    {% if printer.getIsUtilityPrinter %}
        <span class="badge rounded-pill text-bg-info">utility</span>
    {% endif %}
</td>
```

- [ ] **Step 6: Commit**

```bash
git add src/Application/Actions/Admin/CreatePrinter.php src/Application/Actions/Admin/UpdatePrinter.php src/templates/admin/create_printer.twig src/templates/admin/update_printer.twig src/templates/admin/printers.twig
git commit -m "feat: add isUtilityPrinter to admin printer CRUD"
```

---

### Task 3: Update GraphQL type

**Files:**
- Modify: `src/Application/GraphQl/Types/PrinterType.php`

- [ ] **Step 1: Add isUtilityPrinter field**

In `src/Application/GraphQl/Types/PrinterType.php`, add `'isUtilityPrinter' => Type::boolean()` to the fields array. The full updated fields array:

```php
'fields' => function ()  {
    return [
        'id' => Type::id(),
        'name' => Type::string(),
        'printerAddress' => Type::string(),
        'isActive' => Type::boolean(),
        'isReceiptPrinter' => Type::boolean(),
        'isUtilityPrinter' => Type::boolean()
    ];
},
```

The existing `FieldResolver` calls `getIsUtilityPrinter()` automatically — no other changes needed.

- [ ] **Step 2: Commit**

```bash
git add src/Application/GraphQl/Types/PrinterType.php
git commit -m "feat: expose isUtilityPrinter in GraphQL PrinterType"
```

---

### Task 4: Update shopping list app

**Files:**
- Modify: `src/templates/shopping_lists_app/update_shopping_list.twig`

- [ ] **Step 1: Add utilityPrinters computed property**

In `src/templates/shopping_lists_app/update_shopping_list.twig`, find the `computed:` block (around line 222). Add `utilityPrinters` after `receiptPrinters`:

```javascript
computed: {
    receiptPrinters() {
        return this.printers.filter(p => p.isReceiptPrinter);
    },
    utilityPrinters() {
        return this.printers.filter(p => p.isUtilityPrinter);
    },
    totalCost() {
        const total = this.selectedSupplies.reduce((sum, s) => sum + (s.price * s.quantity), 0);
        return Math.round(total * 100) / 100;
    },
},
```

- [ ] **Step 2: Replace the receipt printer print button block with utility printer block**

Find the existing print button block (lines 25–57) that reads `receiptPrinters`. Replace it entirely with the `utilityPrinters` equivalent:

```html
<template v-if="utilityPrinters.length === 1">
    <button
        type="button"
        class="btn btn-lg"
        :class="selectedSupplies.length > 0 ? 'btn-outline-primary' : 'btn-secondary'"
        :disabled="selectedSupplies.length === 0 || printing"
        @click="print(utilityPrinters[0])"
    >
        <i class="bi bi-printer"></i>
        <span class="d-none d-sm-inline ms-1">{{utilityPrinters[0].name}}</span>
    </button>
</template>
<template v-else-if="utilityPrinters.length > 1">
    <div class="btn-group">
        <button
            type="button"
            class="btn btn-lg"
            :class="selectedSupplies.length > 0 ? 'btn-primary' : 'btn-secondary'"
            :disabled="selectedSupplies.length === 0 || printing"
            data-bs-toggle="dropdown"
            aria-expanded="false"
        >
            <i class="bi bi-printer"></i>
            <span v-if="selectedSupplies.length > 0" class="badge bg-light text-dark ms-1">@{{selectedSupplies.length}}</span>
            <i class="bi bi-chevron-down ms-1"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li v-for="printer in utilityPrinters">
                <a class="dropdown-item fs-5 py-2" href="#" @click.prevent="print(printer)">@{{printer.name}}</a>
            </li>
        </ul>
    </div>
</template>
```

When `utilityPrinters.length === 0` neither template renders — the print button is simply absent.

- [ ] **Step 3: Commit**

```bash
git add src/templates/shopping_lists_app/update_shopping_list.twig
git commit -m "feat: restrict shopping list printing to utility printers"
```

---

### Task 5: Write and run migration script

**Files:**
- Create: `migrations/add_is_utility_printer_to_printers.php`

- [ ] **Step 1: Create the migration script**

Create `migrations/add_is_utility_printer_to_printers.php`:

```php
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

// Guard: exit if migration already ran
$columnExists = $pdo->query("
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_name = 'printers' AND column_name = 'is_utility_printer'
")->fetchColumn();

if ($columnExists) {
    die("Column 'is_utility_printer' already exists on 'printers'. Migration may have already run.\n");
}

$pdo->beginTransaction();

try {
    $pdo->exec("ALTER TABLE printers ADD COLUMN is_utility_printer BOOLEAN NOT NULL DEFAULT FALSE");
    echo "Added column 'is_utility_printer' to 'printers'.\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
```

- [ ] **Step 2: Commit**

```bash
git add migrations/add_is_utility_printer_to_printers.php
git commit -m "feat: add migration for is_utility_printer column on printers"
```

- [ ] **Step 3: Run the migration**

```bash
ddev exec php migrations/add_is_utility_printer_to_printers.php
```

Expected output:
```
Connected to database 'db'.
Added column 'is_utility_printer' to 'printers'.
Migration completed successfully.
```
