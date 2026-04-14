# Design: Print Prediction to Thermal Printer

**Date:** 2026-04-14

## Problem

The prediction page shows per-section item quantities but has no way to send them to a thermal printer. Kitchen staff need a printed prep list showing predicted quantities per item.

## Solution

Add a printer-selector button and canvas-based thermal print flow to `predict.twig`, following the same pattern as `print_recipe.twig`. The receipt shows only the date and predicted item quantities grouped by section. `Predict.php` is extended to fetch and pass all active printers.

## Changes to `Predict.php`

- Inject `PrintersRepository` via constructor (alongside existing deps)
- Fetch all active printers: `$this->printersRepository->findBy(['isActive' => true], ['name' => 'asc'])`
- Serialize to a JSON array and pass as `printersJson` to the template:
  ```php
  $printersData = array_values(array_map(fn($p) => [
      'id'             => $p->getId(),
      'name'           => $p->getName(),
      'printerAddress' => $p->getPrinterAddress(),
  ], $printers));
  ```
- Pass `'printersJson' => json_encode($printersData)` in the `twig->render()` call alongside existing vars

## Changes to `predict.twig`

### Printer button UI

Add to the existing `btn-toolbar` div in the page header (alongside the prev/next nav):

```
{% verbatim %}
<template v-if="printers.length === 1">
    <button ... @click="print(printers[0])">...</button>
</template>
<template v-else-if="printers.length > 1">
    <div class="btn-group">
        <button ... data-bs-toggle="dropdown">...</button>
        <ul class="dropdown-menu">
            <li v-for="printer in printers">
                <a ... @click.prevent="print(printer)">{{printer.name}}</a>
            </li>
        </ul>
    </div>
</template>
{% endverbatim %}
```

All active printers are shown — no filtering by type.

### Vue app

Add `{% verbatim %}<div id="app">...</div>{% endverbatim %}` wrapping the existing page content. Vue `data()`:
```js
{
    printers: printersData,   // from PHP
    printing: false,
    printStatus: null,
    canvasCtx: null,
}
```
`mounted()` sets `this.canvasCtx = document.querySelector('canvas').getContext('2d')`.

### Canvas and print method

Add `<canvas width="576" height="10000" class="d-none"></canvas>` before `{% endverbatim %}`.

Add a `{% block javascript %}` section with:

**Drawing primitives** (same as `print_recipe.twig`):
- `centerText(ctx, text, fontSize, bold)` — centered text, advances `y`
- `leftText(ctx, text, fontSize, bold)` — left-padded text, advances `y`
- `separator(ctx)` — horizontal rule, advances `y`

**`buildReceiptXml()`** draws:
```
     ΠΡΟΒΛΕΨΗ          ← centerText, bold, 40px
  Mon 14 Apr 2026      ← centerText, 30px
──────────────────
SECTION NAME           ← leftText, bold, 32px
Item name  x3          ← leftText, 30px  ("name  x" + Math.round(count))
Item name  x1
──────────────────
SECTION NAME
...
```

Each section is followed by a `separator()`. Items with `Math.round(count) === 0` are skipped.

**`print(printer)`** method:
```js
async print(printer) {
    this.printing = true;
    this.printStatus = null;
    try {
        const xml = this.buildReceiptXml();
        await axios.post('/admin/print-jobs/create', { printer: printer.name, xml });
        this.printStatus = 'printed';
    } catch (e) {
        this.printStatus = 'error';
        alert('Σφάλμα εκτύπωσης: ' + e);
    } finally {
        this.printing = false;
    }
}
```

### Data passed to template from PHP

`predict.twig` receives `predictionJson` (JSON-encoded prediction array for Vue) alongside the existing Twig variables (`prediction`, `date`, `prev`, `next`). The Vue app reads section/item data from `predictionJson` — the existing Twig loop for the HTML table is untouched.

The `predictionJson` shape passed from `Predict.php`:
```php
$predictionJson = json_encode([
    'date'         => $date->format('D j M Y'),
    'menuSections' => array_values(array_map(fn($s) => [
        'name'      => $s['menuSection']->getTranslation('el')->getName(),
        'menuItems' => array_values(array_map(fn($item) => [
            'name'  => $item['menuItem']->getTranslation('el')->getName(),
            'count' => $item['count'],
        ], array_values($s['menuItems']))),
    ], array_values($prediction['menuSections']))),
]);
```

## Out of Scope

- Filtering printers by type
- Printing the full metric summary (sales, food cost, covers, etc.)
- Any new routes or actions
