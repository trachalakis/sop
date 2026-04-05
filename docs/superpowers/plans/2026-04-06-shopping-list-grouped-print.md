# Shopping List Grouped Print Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Change the receipt printout to group supplies by supply group instead of printing them as a flat list.

**Architecture:** Single change inside `buildReceiptXml()` in the Vue component — replace the flat `selectedSupplies.forEach` loop with a loop over `supplyGroups`, collecting selected items per group, printing a group header when a group has selections, then the items. No PHP, no data-layer, no Vue state changes.

**Tech Stack:** JavaScript (Vue 3), HTML Canvas (receipt rendering), Twig

---

### Task 1: Replace flat print loop with grouped print loop

**Files:**
- Modify: `src/templates/shopping_lists_app/update_shopping_list.twig` (inside `buildReceiptXml()`, the supply iteration block)

- [ ] **Step 1: Read the file and locate the block to replace**

Read `src/templates/shopping_lists_app/update_shopping_list.twig`. Find the `buildReceiptXml()` method. Locate this exact block (starts just after `y += 36;` and the `const LINE_SIZE = 28;` / `const MAX_CHARS = 31;` declarations):

```javascript
let total = 0;
this.selectedSupplies.forEach(supply => {
    if (supply.quantity <= 0) return;

    const cost = supply.price * supply.quantity;
    total += cost;
    const costStr = `€${(Math.round(cost * 100) / 100).toFixed(2)}`;

    const qty = supply.quantity % 1 === 0
        ? supply.quantity.toString()
        : supply.quantity.toFixed(1);

    let unitBlock = '';
    if (supply.priceUnit == 'item') {
        unitBlock += `${qty}x`;
    } else {
        unitBlock += `${qty}${supply.priceUnit}`;
    }

    const name = supply.name;
    const dots = '.'.repeat(Math.max(1, MAX_CHARS - name.length - unitBlock.length - costStr.length));
    leftText(this.canvasCtx, `${unitBlock} ${name}${dots}${costStr}`, LINE_SIZE, true);
});
y += 36;
separator(this.canvasCtx);
```

- [ ] **Step 2: Replace the block with the grouped version**

Replace the block identified in Step 1 with:

```javascript
let total = 0;
this.supplyGroups.forEach(group => {
    const groupItems = group.supplies
        .map(s => this.getSelection(s))
        .filter(s => s && s.quantity > 0);

    if (groupItems.length === 0) return;

    leftText(this.canvasCtx, group.name.toUpperCase(), LINE_SIZE, true);

    groupItems.forEach(supply => {
        const cost = supply.price * supply.quantity;
        total += cost;
        const costStr = `€${(Math.round(cost * 100) / 100).toFixed(2)}`;

        const qty = supply.quantity % 1 === 0
            ? supply.quantity.toString()
            : supply.quantity.toFixed(1);

        let unitBlock = '';
        if (supply.priceUnit == 'item') {
            unitBlock += `${qty}x`;
        } else {
            unitBlock += `${qty}${supply.priceUnit}`;
        }

        const name = supply.name;
        const dots = '.'.repeat(Math.max(1, MAX_CHARS - name.length - unitBlock.length - costStr.length));
        leftText(this.canvasCtx, `${unitBlock} ${name}${dots}${costStr}`, LINE_SIZE, false);
    });

    y += PAD;
});
y += 36;
separator(this.canvasCtx);
```

**What changed:**
- Outer loop is now `this.supplyGroups.forEach(group => ...)` instead of `this.selectedSupplies.forEach`
- `groupItems` collects selected supplies for each group using the existing `getSelection()` method (which looks up by `supply.id` in `selectedSupplies`), filtering out unselected or zero-quantity items
- Groups with no selected items are skipped entirely
- Group name is printed bold (`true`) before its items
- Individual items are printed non-bold (`false`) to create visual hierarchy (group name stands out)
- `y += PAD` adds a small gap after each group's items
- `total` accumulation and the separator/total block below are unchanged

- [ ] **Step 3: Verify the logic**

Manually trace through the logic to confirm correctness:

1. `group.supplies` — the full list of supplies in the group (from `supplyGroupsData`, passed from the server)
2. `.map(s => this.getSelection(s))` — for each supply, `getSelection` does `this.selectedSupplies.find(sel => sel.id === s.id)`, returning the selected supply object (with `quantity`) or `undefined`
3. `.filter(s => s && s.quantity > 0)` — keeps only supplies that are selected AND have a positive quantity
4. If `groupItems.length === 0`, the group is skipped — no header printed
5. Otherwise the group name is printed bold, then each item in the existing format

- [ ] **Step 4: Commit**

```bash
git add src/templates/shopping_lists_app/update_shopping_list.twig
git commit -m "feat: group supplies by supply group in shopping list printout"
```
