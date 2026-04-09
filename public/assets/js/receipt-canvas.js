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
        const timingEntries = entries
            .filter(e => e.timing === i)
            .sort((a, b) => (a.menuItem.menuPosition ?? 0) - (b.menuItem.menuPosition ?? 0));

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
