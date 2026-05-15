# Barcode Scanning for Take-Out Create Order — Design Spec

## Goal

Allow staff to add menu items to a take-out order by scanning a barcode with a USB or Bluetooth keyboard-wedge barcode scanner, making order entry faster for packaged products.

## Context

Some menu items already carry manufacturer barcodes (EAN/UPC). Others will have barcodes generated and printed specifically for them. Either way, the barcode value must be stored on the menu item and matched at scan time.

The take-out create order page (`/take-out/create`) already loads all menu items for the active "Take Out" menu via GraphQL on mount. Scanning should work against this already-loaded data — no additional server round-trip needed per scan.

---

## Section 1 — Data Layer

### Migration

A standalone PHP migration script (`migrations/add_barcode_to_menu_items.php`) adds a nullable `barcode VARCHAR(64)` column to the `menu_items` table. All existing rows default to `NULL`.

### MenuItem Entity

Add two methods to `src/Domain/Entities/MenuItem.php`:

```php
#[ORM\Column(type: 'string', name: 'barcode', nullable: true)]
private ?string $barcode = null;

public function getBarcode(): ?string { return $this->barcode; }
public function setBarcode(?string $barcode): void { $this->barcode = $barcode; }
```

### GraphQL

Add `barcode` to `MenuItemType` in `src/Application/GraphQl/Types/MenuItemType.php`:

```php
'barcode' => Type::string(),
```

The take-out page's GraphQL query is updated to request `barcode` alongside existing fields.

---

## Section 2 — Admin UI

### Template

A "Barcode" text input is added to `src/templates/admin/update_menu_item.twig`, in the same form section as price and position. It is optional (no `required`).

### Action

`src/Application/Actions/Admin/UpdateMenuItem.php` already processes the POST body. One extra line is added:

```php
$menuItem->setBarcode($requestData['barcode'] ?? null ?: null);
```

The `?: null` collapses an empty string (form field cleared) to `NULL`.

No new route or action is needed.

---

## Section 3 — Take-Out Page Scanning

### Keyboard-Wedge Detection

A global `keydown` listener is registered in `mounted()` and cleaned up in `unmounted()`. It maintains:
- `barcodeBuffer`: array of accumulated characters
- `barcodeFirstKeyAt`: timestamp (ms) of the first character in the current sequence

**On a non-Enter keypress:**
- Append the character to `barcodeBuffer`
- If the buffer was empty, record `barcodeFirstKeyAt = Date.now()`

**On Enter:**
- If `barcodeBuffer.length >= 6` and `Date.now() - barcodeFirstKeyAt <= 500`: treat as a barcode scan
- Look up the barcode in `this.menuSections` (flat scan of all items)
- Clear `barcodeBuffer` and `barcodeFirstKeyAt` regardless of match

The 500ms window comfortably covers all barcode scanner speeds while being much shorter than human typing. Minimum 6 characters excludes accidental short keypresses.

### Barcode Lookup and Item Dispatch

```
const allItems = this.menuSections.flatMap(s => s.menuItems);
const matched = allItems.find(item => item.barcode === scannedCode);
```

- **No match:** show toast "Δεν βρέθηκε" for 1.5s.
- **Match — simple item** (fixed price, no extras): call `addOrderEntry({ menuItem: matched, timing: isDrink ? 6 : 1, family: 1, notes: '', orderEntryExtras: [] })` directly.
- **Match — needs input** (kg-priced or has extras): call `showMenuItemOptionsModal(matched)`, opening the existing options modal so the operator can fill in weight/extras before confirming.

### Visual Feedback Toast

A small fixed-position toast (bottom-center, Bootstrap `toast` component) displays for 1.5 seconds after each scan:
- Success: item name in green
- Not found: "Δεν βρέθηκε" in red

The toast is part of the Vue app's template, controlled by a reactive `scanToast: { visible: false, message: '', success: true }` data property.

---

## Files Changed

| File | Change |
|---|---|
| `migrations/add_barcode_to_menu_items.php` | Create — adds `barcode` column |
| `src/Domain/Entities/MenuItem.php` | Add `barcode` column, getter, setter |
| `src/Application/GraphQl/Types/MenuItemType.php` | Add `barcode` field |
| `src/templates/admin/update_menu_item.twig` | Add barcode text input |
| `src/Application/Actions/Admin/UpdateMenuItem.php` | Persist `barcode` from POST body |
| `src/templates/take_out_app/create_order.twig` | GraphQL query + keydown listener + toast |
