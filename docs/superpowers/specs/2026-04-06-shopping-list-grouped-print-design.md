# Shopping List Grouped Print — Design Spec

**Date:** 2026-04-06
**Scope:** Change the receipt printout of the shopping list to group items by supply group instead of printing them as a flat list.

---

## Background

The shopping list UI already displays supplies grouped by supply group. The print function (`buildReceiptXml()`) in `src/templates/shopping_lists_app/update_shopping_list.twig` iterates over `selectedSupplies` as a flat array, ignoring group structure. The grouped data (`supplyGroups`) is already available on the Vue component.

---

## Change

**File:** `src/templates/shopping_lists_app/update_shopping_list.twig`
**Location:** `buildReceiptXml()` method, the supply iteration block (approx. lines 311–332)

Replace the flat `selectedSupplies.forEach(...)` loop with a grouped loop over `this.supplyGroups`:

1. Iterate `this.supplyGroups` (already sorted by position from the server)
2. For each group, collect supplies from `group.supplies` that are also in `selectedSupplies` with `quantity > 0`
3. If no supplies are selected for a group, skip it (print no header)
4. If any supplies are selected, print the group name as a bold left-aligned line (same `LINE_SIZE` font), then print each item using the existing format (quantity + name + dots + cost)
5. Add a small vertical gap (`y += PAD`) after each group's items for readability

The total accumulation, separator, and total line at the bottom are unchanged.

---

## Receipt Layout (new)

```
ΛΙΣΤΑ ΠΡΟΜΗΘΕΙΩΝ
dd/mm/yyyy  hh:mm
─────────────────
[notes if any]
─────────────────

GROUP NAME A
1x Item......€1.00
2kg Item.....€2.00

GROUP NAME B
3x Item......€3.00

─────────────────
ΣΥΝΟΛΟ: €6.00
```

---

## Constraints

- No PHP changes
- No changes to Vue data, state, save flow, or the UI rendering
- No changes to the canvas dimensions or other print helpers (`centerText`, `leftText`, `separator`)
