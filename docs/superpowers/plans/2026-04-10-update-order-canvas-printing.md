# Update Order Canvas Printing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the ESC/POS text-command receipt printing in `update_order.twig` with the same HTML canvas → bitmap → ePOS image approach used in `create_order.twig`, and extract the shared drawing logic into a reusable JS module.

**Architecture:** A new standalone JS file (`receipt-canvas.js`) exports a single `drawReceiptOnCanvas` function that handles both kitchen printers (filter by item assignment) and receipt printers (all non-zero-price items). Both `create_order.twig` and `update_order.twig` include this file and call the function from their `printOrder()` Vue methods.

**Tech Stack:** Vanilla JS, HTML Canvas API, Epson ePOS SDK (`epson.ePOSBuilder`), Vue 3, Lodash, Axios.

---

### Task 1: Create `public/assets/js/receipt-canvas.js`

**Files:**
- Create: `public/assets/js/receipt-canvas.js`

This file has no framework dependency. It exposes one global function `drawReceiptOnCanvas` used by both order templates.

- [ ] **Step 1: Create the file**

Create `public/assets/js/receipt-canvas.js` with the following content:

```javascript
/**
 * Draws a kitchen/order receipt onto a 2D canvas context.
 *
 * @param {CanvasRenderingContext2D} ctx         - Canvas context (576px wide).
 * @param {{ id: number, isReceiptPrinter: bool }} printer
 * @param {Array}  entries  - Flat array of order entries. Each entry has:
 *     timing (0-6), quantity, weight, notes, family,
 *     menuItemPrice, menuItem.printers [{id}], menuItem.translations [{name, language}],
 *     orderEntryExtras [{name}]
 * @param {{ waiterName: string, label: string, tableName: string,
 *           adults: number, minors: number, notes: string }} header
 * @param {{ hasManyFamilies: bool,
 *           getTranslation: (menuItem, langCode) => {name: string},
 *           calculateOrderEntryPrice: (orderEntry) => number }} helpers
 * @returns {{ orderEntriesCount: number, canvasHeight: number }}
 */
function drawReceiptOnCanvas(ctx, printer, entries, header, helpers) {
    const W = 576;
    const PAD = 3;
    const INDENT = 40;
    let y = 0;

    ctx.clearRect(0, 0, W, 1500);
    ctx.fillStyle = 'white';
    ctx.fillRect(0, 0, W, 1500);
    ctx.fillStyle = 'black';

    const centerText = (text, fontSize, bold = false) => {
        ctx.font = `${bold ? 'bold ' : ''}${fontSize}px monospace`;
        ctx.textAlign = 'center';
        y += fontSize;
        ctx.fillText(text, W / 2, y);
        y += 8;
    };

    const leftText = (text, fontSize, bold = false, indent = 0) => {
        ctx.font = `${bold ? 'bold ' : ''}${fontSize}px monospace`;
        ctx.textAlign = 'left';
        y += fontSize;
        ctx.fillText(text, PAD + indent, y);
        y += 8;
    };

    const separator = () => {
        y += 8;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(W, y);
        ctx.stroke();
        y += 12;
    };

    // Header
    const today = new Date();
    const hm = `${today.getHours()}:${today.getMinutes() < 10 ? '0' + today.getMinutes() : today.getMinutes()}`;
    y += 16;
    centerText(`${header.waiterName} / ${hm}`, 22);
    y += 8;
    centerText(header.label, 44, true);
    y += 10;
    centerText(`Τραπέζι ${header.tableName}`, 64);
    y += 6;
    centerText(`Άτομα ${header.adults} / ${header.minors}`, 40);

    if (header.notes && header.notes.length > 0) {
        y += 8;
        centerText('***', 28);
        header.notes.split('\n').forEach(line => centerText(line, 28));
        centerText('***', 28);
    }

    y += 8;
    separator();

    // Order entries grouped by timing (0-6)
    let orderEntriesCount = 0;

    for (let i = 0; i < 7; i++) {
        const timingEntries = entries.filter(e => e.timing === i);

        const filteredEntries = printer.isReceiptPrinter
            ? timingEntries.filter(e => e.menuItemPrice > 0)
            : timingEntries.filter(e =>
                e.menuItem.printers.some(p => p.id === printer.id)
              );

        filteredEntries.forEach(orderEntry => {
            orderEntriesCount++;
            const quantity = orderEntry.quantity;
            const name = helpers.getTranslation(orderEntry.menuItem, 'el').name;
            const nameEn = helpers.getTranslation(orderEntry.menuItem, 'en').name;
            const weight = orderEntry.weight != null ? ` ${orderEntry.weight / 1000}Kg` : '';

            if (printer.isReceiptPrinter) {
                const price = '€' + helpers.calculateOrderEntryPrice(orderEntry);
                leftText(`${quantity} \xd7 ${name} (${price})${weight}`, 30, true);
            } else {
                let tickboxes = '';
                if (quantity > 1 && nameEn !== 'Bread') {
                    for (let j = 0; j < quantity; j++) tickboxes += '[]';
                }
                let mainLine = `${quantity} \xd7 ${name}${weight}`;
                if (quantity <= 6) mainLine += ` ${tickboxes}`;
                leftText(mainLine, 30, true);

                if (quantity > 6 && tickboxes) {
                    leftText(tickboxes, 26, false, INDENT);
                }
            }

            if (helpers.hasManyFamilies) {
                leftText(`(F${orderEntry.family})`, 24, false, INDENT);
            }

            orderEntry.orderEntryExtras.forEach(extra => {
                leftText(extra.name, 24, false, INDENT);
            });

            if (orderEntry.notes && orderEntry.notes.length > 0) {
                leftText(orderEntry.notes, 24, false, INDENT);
            }
        });

        // Separator between non-empty timing groups
        if (filteredEntries.length > 0) {
            let hasMore = false;
            for (let j = i + 1; j < 7; j++) {
                const nextEntries = printer.isReceiptPrinter
                    ? entries.filter(e => e.timing === j && e.menuItemPrice > 0)
                    : entries.filter(e => e.timing === j && e.menuItem.printers.some(p => p.id === printer.id));
                if (nextEntries.length > 0) {
                    hasMore = true;
                    break;
                }
            }
            if (hasMore) separator();
        }
    }

    return { orderEntriesCount, canvasHeight: y + 20 };
}
```

- [ ] **Step 2: Commit**

```bash
git add public/assets/js/receipt-canvas.js
git commit -m "feat: add shared receipt-canvas.js drawing module"
```

---

### Task 2: Update `create_order.twig` to use the shared module

**Files:**
- Modify: `src/templates/orders_app/create_order.twig`

The inline `drawReceiptOnCanvas()` Vue method (lines 733–845) is deleted and replaced by a call to the global `drawReceiptOnCanvas` from `receipt-canvas.js`. The `printOrder()` method signature and orchestration logic are unchanged.

- [ ] **Step 1: Add the script include after the epos include**

In `src/templates/orders_app/create_order.twig`, find:

```twig
	<script src="/assets/js/epos-2.24.0.js"></script>
```

Replace with:

```twig
	<script src="/assets/js/epos-2.24.0.js"></script>
	<script src="/assets/js/receipt-canvas.js"></script>
```

- [ ] **Step 2: Remove the inline `drawReceiptOnCanvas` Vue method and replace `printOrder`**

Find the entire `drawReceiptOnCanvas` method and `printOrder` method (from `drawReceiptOnCanvas(ctx, printer) {` through the closing `},` of `printOrder`) and replace with the new `printOrder` only:

Find:
```javascript
		    	drawReceiptOnCanvas(ctx, printer) {
		    		const W = 576;
		    		const PAD = 3;
		    		const INDENT = 40;
		    		let y = 0;

		    		ctx.clearRect(0, 0, W, 1500);
		    		ctx.fillStyle = 'white';
		    		ctx.fillRect(0, 0, W, 1500);
		    		ctx.fillStyle = 'black';

		    		const centerText = (text, fontSize, bold = false) => {
		    			ctx.font = `${bold ? 'bold ' : ''}${fontSize}px monospace`;
		    			ctx.textAlign = 'center';
		    			y += fontSize;
		    			ctx.fillText(text, W / 2, y);
		    			y += 8;
		    		};

		    		const leftText = (text, fontSize, bold = false, indent = 0) => {
		    			ctx.font = `${bold ? 'bold ' : ''}${fontSize}px monospace`;
		    			ctx.textAlign = 'left';
		    			y += fontSize;
		    			ctx.fillText(text, PAD + indent, y);
		    			y += 8;
		    		};

		    		const separator = () => {
		    			y += 8;
		    			ctx.lineWidth = 2;
                        ctx.beginPath();
		    			ctx.moveTo(0, y);
		    			ctx.lineTo(W, y);
		    			ctx.stroke();
		    			y += 12;
		    		};

		    		// Header
		    		y += 16;
		    		centerText(`${this.waiter.fullName} / ` + this.getHm().trim(), 22);
		    		y += 8;
		    		centerText('ΝΕΑ ΠΑΡΑΓΓΕΛΙΑ', 44, true);
		    		y += 10;
		    		centerText(`Τραπέζι ${this.order.table.name}`, 64);
		    		y += 6;
		    		centerText(`Άτομα ${this.order.adults} / ${this.order.minors}`, 40);

		    		if (this.order.notes.length > 0) {
		    			y += 8;
		    			centerText('***', 28);
		    			this.order.notes.split('\n').forEach(line => centerText(line, 28));
		    			centerText('***', 28);
		    		}

		    		y += 8;
		    		separator();

		    		// Order entries grouped by timing
		    		let orderEntriesCount = 0;

		    		for (let i = 0; i < 7; i++) {
		    			let entries = this.orderedEntries(i).filter(orderEntry =>
		    				_.findIndex(orderEntry.menuItem.printers, s => s.id == printer.id) != -1
		    			);

		    			entries.forEach(orderEntry => {
		    				orderEntriesCount++;
		    				let quantity = orderEntry.quantity;
		    				let name = this.getTranslation(orderEntry.menuItem, 'el').name;
		    				let nameEn = this.getTranslation(orderEntry.menuItem, 'en').name;

		    				let weight = orderEntry.weight != null ? ` ${orderEntry.weight / 1000}Kg` : '';

		    				let tickboxes = '';
		    				if (quantity > 1 && nameEn != 'Bread') {
		    					for (let j = 0; j < quantity; j++) tickboxes += '[]';
		    				}

		    				let mainLine = `${quantity} \xd7 ${name}${weight}`;
		    				if (quantity <= 6) mainLine += ` ${tickboxes}`;
		    				leftText(mainLine, 30, true);

		    				if (quantity > 6 && tickboxes) {
		    					leftText(tickboxes, 26, false, INDENT);
		    				}

		    				if (this.orderHasManyFamilies()) {
		    					leftText(`(F${orderEntry.family})`, 24, false, INDENT);
		    				}

		    				orderEntry.orderEntryExtras.forEach(extra => {
		    					leftText(extra.name, 24, false, INDENT);
		    				});

		    				if (orderEntry.notes.length > 0) {
		    					leftText(orderEntry.notes, 24, false, INDENT);
		    				}
		    			});

		    			// Separator between non-empty timing groups
		    			if (entries.length > 0) {
		    				let hasMore = false;
		    				for (let j = i + 1; j < 7; j++) {
		    					if (this.orderedEntries(j).some(oe => _.findIndex(oe.menuItem.printers, s => s.id == printer.id) != -1)) {
		    						hasMore = true;
		    						break;
		    					}
		    				}
		    				if (hasMore) separator();
		    			}
		    		}

		    		return { orderEntriesCount, canvasHeight: y + 20 };
		    	},
		    	printOrder() {
		    		_.each(this.printers, printer => {
		    			let pr = new epson.ePOSBuilder();

		    			let { orderEntriesCount, canvasHeight } = this.drawReceiptOnCanvas(this.canvasCtx, printer);

		    			if (orderEntriesCount > 0) {
		    				pr.addImage(this.canvasCtx, 0, 0, 576, canvasHeight, pr.COLOR_1, pr.MODE_MONO);
                            pr.addFeedLine(5);
                            pr.addCut(pr.CUT_FEED);
		    				axios.post(
		    					'/admin/print-jobs/create',
		    					{
		    						printer: printer.name,
		    						xml: pr.toString()
		    					}
		    				).then(response => {
		    					if (response.data == 'ok') {
		    						printer.printOk = true;
		    					}
		    				}).catch(function (error) {
		    					alert(error);
		    				});
		    			} else {
		    				printer.printOk = true;
		    			}
		    		});
		    	},
```

Replace with:

```javascript
		    	printOrder() {
		    		_.each(this.printers, printer => {
		    			let pr = new epson.ePOSBuilder();

		    			let { orderEntriesCount, canvasHeight } = drawReceiptOnCanvas(
		    				this.canvasCtx,
		    				printer,
		    				this.order.orderEntries,
		    				{
		    					waiterName: this.waiter.fullName,
		    					label: 'ΝΕΑ ΠΑΡΑΓΓΕΛΙΑ',
		    					tableName: this.order.table.name,
		    					adults: this.order.adults,
		    					minors: this.order.minors,
		    					notes: this.order.notes
		    				},
		    				{
		    					hasManyFamilies: this.orderHasManyFamilies(),
		    					getTranslation: this.getTranslation.bind(this),
		    					calculateOrderEntryPrice: this.calculateOrderEntryPrice.bind(this)
		    				}
		    			);

		    			if (orderEntriesCount > 0) {
		    				pr.addImage(this.canvasCtx, 0, 0, 576, canvasHeight, pr.COLOR_1, pr.MODE_MONO);
		    				pr.addFeedLine(5);
		    				pr.addCut(pr.CUT_FEED);
		    				axios.post(
		    					'/admin/print-jobs/create',
		    					{
		    						printer: printer.name,
		    						xml: pr.toString()
		    					}
		    				).then(response => {
		    					if (response.data == 'ok') {
		    						printer.printOk = true;
		    					}
		    				}).catch(function (error) {
		    					alert(error);
		    				});
		    			} else {
		    				printer.printOk = true;
		    			}
		    		});
		    	},
```

- [ ] **Step 3: Verify in browser**

Open `https://sop.ddev.site/orders-app/create` and place a test order. After saving, confirm that kitchen slips print as before (canvas bitmap, no regression).

- [ ] **Step 4: Commit**

```bash
git add src/templates/orders_app/create_order.twig
git commit -m "refactor: use shared receipt-canvas.js in create_order"
```

---

### Task 3: Update `update_order.twig` to use canvas printing

**Files:**
- Modify: `src/templates/orders_app/update_order.twig`

Four changes: add `<canvas>` element, include `receipt-canvas.js`, add `canvasCtx` to Vue data + mount init, replace the ESC/POS `printOrder()` with a canvas-based version.

- [ ] **Step 1: Add the `<canvas>` element**

In `src/templates/orders_app/update_order.twig`, find:

```twig
</div>
{% endverbatim %}{% endblock %}
```

Replace with:

```twig
</div>
<canvas width="576" height="1500" class="d-none"></canvas>
{% endverbatim %}{% endblock %}
```

- [ ] **Step 2: Add the script include**

Find:

```twig
	<script src="/assets/js/epos-2.24.0.js"></script>
```

Replace with:

```twig
	<script src="/assets/js/epos-2.24.0.js"></script>
	<script src="/assets/js/receipt-canvas.js"></script>
```

- [ ] **Step 3: Add `canvasCtx` to Vue data**

Find:

```javascript
		    		orderEntryForTransfer: null
		    	}
		    },
```

Replace with:

```javascript
		    		orderEntryForTransfer: null,

		    		canvasCtx: null
		    	}
		    },
```

- [ ] **Step 4: Initialize `canvasCtx` in `mounted()`**

Find:

```javascript
		    mounted () {
		    	let id = new URLSearchParams(window.location.search).get('id');
```

Replace with:

```javascript
		    mounted () {
		    	let id = new URLSearchParams(window.location.search).get('id');
		    	this.canvasCtx = document.querySelector('canvas').getContext('2d');
```

- [ ] **Step 5: Replace `printOrder()` with canvas-based version**

Find the entire `printOrder()` method body (from `printOrder() {` through its closing `},`):

```javascript
		    	printOrder() {
					if (this.newOrderEntryGroup.orderEntries.length == 0) {
						_.each(this.printers, printer => {
							printer.printOk = true;
						});
						return;
					}
					_.each(this.printers, printer => {
						let ePOSBuilder = new epson.ePOSBuilder();

						ePOSBuilder.addCommand('\x1b\x74\x26'); //ESC t 16 (1252 code table)
```

(This block runs from line 1310 to line 1455 — the `},` before `}` then `}).mount('#app')`)

Replace the entire `printOrder()` method with:

```javascript
		    	printOrder() {
		    		if (this.newOrderEntryGroup.orderEntries.length == 0) {
		    			_.each(this.printers, printer => {
		    				printer.printOk = true;
		    			});
		    			return;
		    		}
		    		_.each(this.printers, printer => {
		    			let pr = new epson.ePOSBuilder();

		    			let { orderEntriesCount, canvasHeight } = drawReceiptOnCanvas(
		    				this.canvasCtx,
		    				printer,
		    				this.newOrderEntryGroup.orderEntries,
		    				{
		    					waiterName: this.waiter.fullName,
		    					label: 'EXTRA',
		    					tableName: this.order.table.name,
		    					adults: this.order.adults,
		    					minors: this.order.minors,
		    					notes: this.newOrderEntryGroup.notes
		    				},
		    				{
		    					hasManyFamilies: this.orderHasManyFamilies,
		    					getTranslation: this.getTranslation.bind(this),
		    					calculateOrderEntryPrice: this.calculateOrderEntryPrice.bind(this)
		    				}
		    			);

		    			if (orderEntriesCount > 0) {
		    				pr.addImage(this.canvasCtx, 0, 0, 576, canvasHeight, pr.COLOR_1, pr.MODE_MONO);
		    				pr.addFeedLine(5);
		    				pr.addCut(pr.CUT_FEED);
		    				axios.post(
		    					'/admin/print-jobs/create',
		    					{
		    						printer: printer.name,
		    						xml: pr.toString()
		    					}
		    				).then(response => {
		    					if (response.data == 'ok') {
		    						printer.printOk = true;
		    					}
		    				}).catch(function (error) {
		    					alert(error);
		    				});
		    			} else {
		    				printer.printOk = true;
		    			}
		    		});
		    	},
```

- [ ] **Step 6: Verify in browser**

Open an existing open order at `https://sop.ddev.site/orders-app/update?id=<id>`, add at least one new item, and save. Confirm that kitchen slips and receipt printer output now use the canvas bitmap format (same visual style as create_order) with the "EXTRA" header label.

- [ ] **Step 7: Commit**

```bash
git add src/templates/orders_app/update_order.twig
git commit -m "feat: use canvas-based printing in update_order"
```
