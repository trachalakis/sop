# Barcode Scanning for Take-Out Create Order — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow staff to add take-out menu items instantly by scanning a barcode with a USB/Bluetooth keyboard-wedge barcode scanner.

**Architecture:** A `barcode` column on `menu_items` stores the barcode string (nullable). The admin update-menu-item form exposes it for data entry. The take-out create-order page registers a global `keydown` listener that detects keyboard-wedge scans (rapid burst of chars + Enter), looks up the item from already-loaded in-memory data, and dispatches it directly into the order — no extra network requests.

**Tech Stack:** PHP 8, Doctrine ORM 3, Twig 3, PostgreSQL, Vue 3 (CDN), GraphQL (webonyx/graphql-php), Bootstrap 5

---

### Task 1: Migration — add barcode column

**Files:**
- Create: `migrations/add_barcode_to_menu_items.php`

- [ ] **Step 1: Create the migration file**

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

$columnExists = $pdo->query("
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_name = 'menu_items' AND column_name = 'barcode'
")->fetchColumn();

if ($columnExists) {
    die("Column 'barcode' already exists on 'menu_items'. Migration may have already run.\n");
}

$pdo->beginTransaction();

try {
    $pdo->exec("ALTER TABLE menu_items ADD COLUMN barcode VARCHAR(64) DEFAULT NULL");
    echo "Added column 'barcode' to 'menu_items' (all existing rows set to NULL).\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
```

- [ ] **Step 2: Run the migration**

```bash
ddev exec php migrations/add_barcode_to_menu_items.php
```

Expected output:
```
Connected to database 'db'.
Added column 'barcode' to 'menu_items' (all existing rows set to NULL).
Migration completed successfully.
```

- [ ] **Step 3: Commit**

```bash
git add migrations/add_barcode_to_menu_items.php
git commit -m "feat: add barcode column to menu_items"
```

---

### Task 2: MenuItem entity + GraphQL type

**Files:**
- Modify: `src/Domain/Entities/MenuItem.php`
- Modify: `src/Application/GraphQl/Types/MenuItemType.php`

- [ ] **Step 1: Add the barcode property and accessors to MenuItem**

In `src/Domain/Entities/MenuItem.php`, add the property after the existing `$isArchived` field declaration (around line 43):

```php
#[ORM\Column(type: 'string', name: 'barcode', nullable: true)]
private ?string $barcode = null;
```

Add the getter after `getAvailableQuantity()` (around line 106):

```php
public function getBarcode(): ?string
{
    return $this->barcode;
}
```

Add the setter after `setAvailableQuantity()` (around line 211):

```php
public function setBarcode(?string $barcode): void
{
    $this->barcode = $barcode;
}
```

- [ ] **Step 2: Expose barcode in the GraphQL MenuItemType**

In `src/Application/GraphQl/Types/MenuItemType.php`, add `barcode` to the fields array (after `'id'`, for example):

```php
'barcode' => Type::string(),
```

The full fields array becomes:

```php
'fields' => function () {
    return [
        'id' => Type::id(),
        'barcode' => Type::string(),
        'availableQuantity' => Type::int(),
        'trackAvailableQuantity' => Type::boolean(),
        'isActive' => Type::boolean(),
        'isDrink' => Type::boolean(),
        'price' => Type::float(),
        'priceUnit' => Type::string(),
        'menuPosition' => Type::int(),
        'translations' => Type::listOf(Types::menuItemTranslation()),
        'extras' => Type::listOf(Types::extra()),
        'allExtras' => Type::listOf(Types::extra()),
        'printers' => Type::listOf(Types::printer()),
    ];
},
```

- [ ] **Step 3: Verify GraphQL resolves barcode**

The existing `FieldResolver` uses `get` + ucfirst on the field name, so `barcode` resolves to `getBarcode()` automatically — no changes needed in `FieldResolver.php`.

Open `https://sop.ddev.site/admin/graph-ql` (POST, body: `{ menuItems: activeMenuItems { barcode } }` on any menuSection query) and confirm the field resolves without error.

- [ ] **Step 4: Commit**

```bash
git add src/Domain/Entities/MenuItem.php src/Application/GraphQl/Types/MenuItemType.php
git commit -m "feat: add barcode field to MenuItem entity and GraphQL type"
```

---

### Task 3: Admin UI — barcode input field

**Files:**
- Modify: `src/templates/admin/update_menu_item.twig`
- Modify: `src/Application/Actions/Admin/UpdateMenuItem.php`

- [ ] **Step 1: Add barcode input to the update form**

In `src/templates/admin/update_menu_item.twig`, add a new row after the position row (after the closing `</div>` of the Θέση row, around line 107):

```twig
<div class="row mb-3">
    <label class="col-sm-2 col-form-label">Barcode</label>
    <div class="col-sm-10">
        <input type="text" class="form-control" name="barcode" value="{{menuItem.getBarcode}}"/>
    </div>
</div>
```

- [ ] **Step 2: Persist barcode in UpdateMenuItem action**

In `src/Application/Actions/Admin/UpdateMenuItem.php`, add one line after `$menuItem->setTrackAvailableQuantity(...)` (line 61):

```php
$menuItem->setBarcode($requestData['barcode'] ?? null ?: null);
```

The `?: null` collapses an empty string submission (field cleared) to `NULL` in the database.

- [ ] **Step 3: Smoke test the admin form**

1. Navigate to any menu item's update page: `https://sop.ddev.site/admin/menu-items/update?id=<any-id>`
2. Confirm the "Barcode" field appears with an empty value
3. Enter a test barcode (e.g., `1234567890`) and save
4. Reload the page — confirm the value is preserved
5. Clear the field and save — confirm it saves as empty (NULL)

- [ ] **Step 4: Commit**

```bash
git add src/templates/admin/update_menu_item.twig src/Application/Actions/Admin/UpdateMenuItem.php
git commit -m "feat: add barcode field to admin menu item update form"
```

---

### Task 4: Take-out page — barcode scanning

**Files:**
- Modify: `src/templates/take_out_app/create_order.twig`

This task adds four things to the existing Vue app in `create_order.twig`:
1. `barcode` added to the GraphQL query
2. Three new reactive data properties
3. `mounted()` registers the keydown handler; new `unmounted()` removes it
4. `handleBarcodeScan()` method
5. Toast HTML in the template

- [ ] **Step 1: Add `barcode` to the GraphQL query**

In `src/templates/take_out_app/create_order.twig`, find the GraphQL query string (around line 396) and add `barcode` to the `menuItems: activeMenuItems` field list:

```javascript
data: `{
    menuSections(menu: "Take Out") {
        id, translations { name, language },
        menuItems: activeMenuItems {
            id, barcode, trackAvailableQuantity, availableQuantity, priceUnit, isDrink, price, translations { name, language }, menuPosition,
            extras: allExtras {
                id, name, price
            },
            printers { id, name, printerAddress }
        }
    },
    printers {
        id, name, printerAddress, isReceiptPrinter
    }
}`
```

- [ ] **Step 2: Add reactive data properties**

In the `data()` return object, add three new properties after `canvasCtx: null`:

```javascript
barcodeBuffer: [],
barcodeFirstKeyAt: null,
scanToast: { visible: false, message: '', success: true },
```

- [ ] **Step 3: Register keydown listener in `mounted()` and clean up in `unmounted()`**

At the end of `mounted()` (after the axios call), store and register the handler:

```javascript
this._barcodeHandler = (e) => {
    const tag = document.activeElement?.tagName?.toLowerCase();
    if (tag === 'input' || tag === 'textarea') {
        this.barcodeBuffer = [];
        this.barcodeFirstKeyAt = null;
        return;
    }
    if (e.key === 'Enter') {
        if (
            this.barcodeBuffer.length >= 6 &&
            this.barcodeFirstKeyAt !== null &&
            Date.now() - this.barcodeFirstKeyAt <= 500
        ) {
            this.handleBarcodeScan(this.barcodeBuffer.join(''));
        }
        this.barcodeBuffer = [];
        this.barcodeFirstKeyAt = null;
    } else if (e.key.length === 1) {
        if (this.barcodeBuffer.length === 0) {
            this.barcodeFirstKeyAt = Date.now();
        }
        this.barcodeBuffer.push(e.key);
    }
};
document.addEventListener('keydown', this._barcodeHandler);
```

Add `unmounted()` as a new lifecycle hook after `mounted()`:

```javascript
unmounted() {
    document.removeEventListener('keydown', this._barcodeHandler);
},
```

- [ ] **Step 4: Add `handleBarcodeScan()` method**

Add this method to the `methods` object (after `isDrink`, for example):

```javascript
handleBarcodeScan(code) {
    const allItems = this.menuSections.flatMap(s => s.menuItems);
    const matched = allItems.find(item => item.barcode === code);

    if (!matched) {
        this.scanToast = { visible: true, message: 'Δεν βρέθηκε', success: false };
        setTimeout(() => { this.scanToast.visible = false; }, 1500);
        return;
    }

    this.scanToast = { visible: true, message: this.getTranslation(matched, 'el').name, success: true };
    setTimeout(() => { this.scanToast.visible = false; }, 1500);

    if (matched.priceUnit === 'kg' || matched.extras.length > 0) {
        this.showMenuItemOptionsModal(matched);
    } else {
        this.addOrderEntry({
            menuItem: matched,
            timing: this.isDrink(matched) ? 6 : 1,
            family: 1,
            notes: '',
            orderEntryExtras: [],
        });
    }
},
```

- [ ] **Step 5: Add toast HTML to template**

In `src/templates/take_out_app/create_order.twig`, add the toast after the `<!-- End modals -->` comment and before the closing `</div>` of the `id="app"` wrapper (around line 331):

```html
<div
    v-if="scanToast.visible"
    class="position-fixed bottom-0 start-50 translate-middle-x mb-4"
    style="z-index: 9999"
>
    <div class="toast show" :class="scanToast.success ? 'text-bg-success' : 'text-bg-danger'">
        <div class="toast-body fw-bold text-center px-4">
            {{scanToast.message}}
        </div>
    </div>
</div>
```

- [ ] **Step 6: Manual smoke test**

1. Open `https://sop.ddev.site/take-out/create`
2. Open browser devtools console
3. Simulate a barcode scan by running this in the console (fires rapid keydown events then Enter):

```javascript
const fire = (key) => document.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));
['1','2','3','4','5','6','7','8','9','0'].forEach(k => fire(k));
fire('Enter');
```

Expected: the "Δεν βρέθηκε" red toast appears for 1.5s (no menu item has that barcode yet).

4. Go to the admin and assign a barcode to one of the Take Out menu items (a simple item with no extras and priceUnit = 'item').
5. Back on the take-out create page, fire the same simulation but using the barcode you just set.

Expected: the item is added to the order and the green toast with its name appears for 1.5s.

- [ ] **Step 7: Commit**

```bash
git add src/templates/take_out_app/create_order.twig
git commit -m "feat: add barcode scanning to take-out create order page"
```
