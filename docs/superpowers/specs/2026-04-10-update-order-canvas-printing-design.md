# Design: Canvas-Based Receipt Printing on Order Update

**Date:** 2026-04-10

## Problem

When creating an order, `create_order.twig` prints kitchen slips to all printers using an HTML canvas → bitmap → ePOS image approach. When updating an order, `update_order.twig` prints new items using raw ESC/POS text commands instead. The goal is to unify both on the canvas approach and eliminate the duplicated drawing logic.

## Solution

Extract the canvas receipt drawing logic into a shared JS module and use it in both `create_order.twig` and `update_order.twig`.

## Shared Module

**File:** `public/assets/js/receipt-canvas.js`

Single exported function:

```
drawReceiptOnCanvas(ctx, printer, entries, header, helpers)
```

**Parameters:**

- `ctx` — 2D canvas context (576px wide, cleared before each draw)
- `printer` — `{ id, isReceiptPrinter }` — controls item filtering and formatting
- `entries` — flat array of order entries; each entry has `timing` (0–6), `menuItem.printers`, `menuItemPrice`, `quantity`, `weight`, `orderEntryExtras`, `notes`, `family`
- `header` — `{ waiterName, label, tableName, adults, minors, notes }` where `label` is `'ΝΕΑ ΠΑΡΑΓΓΕΛΙΑ'` for new orders and `'EXTRA'` for updates
- `helpers` — `{ hasManyFamilies: bool, getTranslation(menuItem, langCode), calculateOrderEntryPrice(orderEntry) }`

**Returns:** `{ orderEntriesCount, canvasHeight }`

**Internal behaviour:**

1. Clear and fill canvas with white
2. Draw header: waiter name + time, bold label, table name, pax, notes (if any), then a separator line
3. Group entries by timing (0–6); for each timing group:
   - Kitchen printers: include entries where `printer.id` appears in `menuItem.printers`
   - Receipt printers (`isReceiptPrinter === true`): include all entries where `menuItemPrice > 0`
   - Draw each entry: quantity × name, weight (if set), tickboxes (kitchen only, quantity > 1), family tag (if `hasManyFamilies`), extras, notes; price appended for receipt printers
   - Draw a separator between non-empty timing groups
4. Return `orderEntriesCount` and `y + 20` as `canvasHeight`

Canvas drawing primitives (`centerText`, `leftText`, `separator`) are defined as closures inside the function, closing over `ctx` and the shared `y` counter.

## Changes to `create_order.twig`

- Add `<script src="/assets/js/receipt-canvas.js"></script>` after the epos script include
- Remove the inline `drawReceiptOnCanvas()` Vue method
- Update `printOrder()` to call `drawReceiptOnCanvas(this.canvasCtx, printer, this.order.orderEntries, { waiterName: this.waiter.fullName, label: 'ΝΕΑ ΠΑΡΑΓΓΕΛΙΑ', tableName: this.order.table.name, adults: this.order.adults, minors: this.order.minors, notes: this.order.notes }, { hasManyFamilies: this.orderHasManyFamilies(), getTranslation: this.getTranslation.bind(this), calculateOrderEntryPrice: this.calculateOrderEntryPrice.bind(this) })`
- Everything else in `printOrder()` (ePOS XML build, POST to `/admin/print-jobs/create`, `printer.printOk` flag) is unchanged

## Changes to `update_order.twig`

- Add `<canvas width="576" height="1500" class="d-none"></canvas>` before `{% endverbatim %}`
- Add `<script src="/assets/js/receipt-canvas.js"></script>` after the epos script include
- Add `canvasCtx: null` to Vue `data()`
- In `mounted()`, add `this.canvasCtx = document.querySelector('canvas').getContext('2d')`
- Replace the ESC/POS `printOrder()` body with a canvas-based version:
  - Early return (mark all `printOk`) if `newOrderEntryGroup.orderEntries` is empty — unchanged
  - Iterate `this.printers`; for each printer call `drawReceiptOnCanvas()` with `this.newOrderEntryGroup.orderEntries` and header label `'EXTRA'`
  - If `orderEntriesCount > 0`: build ePOS XML with `pr.addImage(...)`, `pr.addFeedLine(5)`, `pr.addCut(pr.CUT_FEED)`, POST to `/admin/print-jobs/create`, set `printer.printOk = true` on success
  - If `orderEntriesCount === 0`: set `printer.printOk = true` immediately
- `printOrderEntryGroup()` (the per-group reprint) is out of scope and unchanged

## Out of Scope

- `printOrderEntryGroup()` reprint function in `update_order.twig`
- `print_order_receipt.twig` (customer receipt page)
- `TakeOutApp/UpdateOrder`
