# Utility Printer Flag — Design Spec

**Date:** 2026-04-09
**Scope:** Add an `isUtilityPrinter` boolean field to the `Printer` entity and wire it through the stack so the shopping list app can only print to utility printers.

---

## Background

Printers are currently tagged with `isReceiptPrinter` to distinguish thermal receipt printers used at tables. A new flag is needed to mark printers dedicated to daily operational documents (shopping lists, reports, etc.). The shopping list app currently prints to receipt printers; this change restricts it to utility printers only, with a picker when multiple utility printers exist.

---

## Section 1: Entity & Migration

### `src/Domain/Entities/Printer.php`

Add one field, getter, and setter:

```php
#[ORM\Column(type: 'boolean', name: 'is_utility_printer')]
private bool $isUtilityPrinter = false;

public function getIsUtilityPrinter(): bool
{
    return $this->isUtilityPrinter;
}

public function setIsUtilityPrinter(bool $isUtilityPrinter): void
{
    $this->isUtilityPrinter = $isUtilityPrinter;
}
```

The property default of `false` ensures Doctrine can instantiate existing records before the migration runs in development.

### `migrations/add_is_utility_printer_to_printers.php`

Standalone PDO migration script following the existing pattern:

- Guard: exit if `is_utility_printer` column already exists in `information_schema.columns` for the `printers` table
- Single transaction with rollback on failure
- SQL: `ALTER TABLE printers ADD COLUMN is_utility_printer BOOLEAN NOT NULL DEFAULT FALSE`

---

## Section 2: Admin CRUD

### `src/Application/Actions/Admin/CreatePrinter.php`

In the POST block, add:
```php
$printer->setIsUtilityPrinter(boolval($requestData['isUtilityPrinter'] ?? false));
```

### `src/Application/Actions/Admin/UpdatePrinter.php`

In the POST block, add:
```php
$printer->setIsUtilityPrinter(boolval($requestData['isUtilityPrinter'] ?? false));
```

### `src/templates/admin/create_printer.twig`

Add a Yes/No select field (defaults to Όχι):
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

### `src/templates/admin/update_printer.twig`

Same field, pre-filled:
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

### `src/templates/admin/printers.twig`

Show a "utility" badge next to each printer that has the flag set, alongside the existing "ανενεργός" badge.

---

## Section 3: GraphQL

### `src/Application/GraphQl/Types/PrinterType.php`

Add one entry to the fields array:
```php
'isUtilityPrinter' => Type::boolean()
```

The existing generic `FieldResolver` calls `getIsUtilityPrinter()` automatically — no resolver changes needed.

---

## Section 4: Shopping List App

### `src/templates/shopping_lists_app/update_shopping_list.twig`

**Computed property** — add alongside `receiptPrinters`:
```javascript
utilityPrinters() {
    return this.printers.filter(p => p.isUtilityPrinter);
},
```

**Print button block** — replace the existing `receiptPrinters` block (lines 25–57) with an identical block using `utilityPrinters`. Behaviour:
- 0 utility printers → block absent (no print button shown)
- 1 utility printer → single button showing the printer name, calls `print(utilityPrinters[0])`
- 2+ utility printers → dropdown listing all utility printers, each calls `print(printer)`

---

## Files Changed

| File | Action |
|---|---|
| `src/Domain/Entities/Printer.php` | Modify |
| `src/Application/Actions/Admin/CreatePrinter.php` | Modify |
| `src/Application/Actions/Admin/UpdatePrinter.php` | Modify |
| `src/templates/admin/create_printer.twig` | Modify |
| `src/templates/admin/update_printer.twig` | Modify |
| `src/templates/admin/printers.twig` | Modify |
| `src/Application/GraphQl/Types/PrinterType.php` | Modify |
| `src/templates/shopping_lists_app/update_shopping_list.twig` | Modify |
| `migrations/add_is_utility_printer_to_printers.php` | Create |
