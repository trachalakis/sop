# Predict Page Thermal Printing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a printer-selector button to the predict page that prints predicted item quantities per section to any active thermal printer using the canvas → ePOS bitmap approach.

**Architecture:** `Predict.php` is extended to inject `PrintersRepository` and pass two JSON blobs to the template: all active printers and a serialized prediction (date + section/item names + counts). `predict.twig` gains a small isolated Vue app (`#print-app`) that renders the printer button(s), draws the receipt on a hidden canvas, and POSTs to `/admin/print-jobs/create`. The existing Twig-rendered content is untouched.

**Tech Stack:** PHP 8, Slim 4, PHP-DI 7, Vue 3, Epson ePOS SDK, axios, HTML Canvas API.

---

### Task 1: Extend `Predict.php` to pass printer and prediction JSON

**Files:**
- Modify: `src/Application/Actions/Admin/Predict.php`

- [ ] **Step 1: Add the `PrintersRepository` import and constructor parameter**

At the top of `src/Application/Actions/Admin/Predict.php`, add the import after the existing `use` lines:

```php
use Domain\Repositories\PrintersRepository;
```

Replace the constructor (lines 18–23) with:

```php
    public function __construct(
        private OrdersRepository $ordersRepository,
        private OrdersReportService $ordersReportService,
        private PrintersRepository $printersRepository,
        private Twig $twig
    ) {
    }
```

- [ ] **Step 2: Serialize printers and prediction, pass to template**

In `__invoke()`, replace the `return $this->twig->render(...)` call (lines 53–62) with:

```php
        $printers = $this->printersRepository->findBy(['isActive' => true], ['name' => 'asc']);
        $printersData = array_values(array_map(fn($p) => [
            'id'             => $p->getId(),
            'name'           => $p->getName(),
            'printerAddress' => $p->getPrinterAddress(),
        ], $printers));

        $predictionData = [
            'date'         => $date->format('D j M Y'),
            'menuSections' => array_values(array_map(fn($s) => [
                'name'      => $s['menuSection']->getTranslation('el')->getName(),
                'menuItems' => array_values(array_map(fn($item) => [
                    'name'  => $item['menuItem']->getTranslation('el')->getName(),
                    'count' => $item['count'],
                ], array_values($s['menuItems']))),
            ], array_values($prediction['menuSections']))),
        ];

        return $this->twig->render(
            $response,
            'admin/predict.twig',
            [
                'date'           => $date,
                'prev'           => (clone $date)->sub(new DateInterval('P1D')),
                'next'           => (clone $date)->add(new DateInterval('P1D')),
                'prediction'     => $prediction,
                'printersJson'   => json_encode($printersData),
                'predictionJson' => json_encode($predictionData),
            ]
        );
```

- [ ] **Step 3: Commit**

```bash
git add src/Application/Actions/Admin/Predict.php
git commit -m "feat: pass printers and prediction JSON to predict.twig"
```

---

### Task 2: Add Vue print app to `predict.twig`

**Files:**
- Modify: `src/templates/admin/predict.twig`

- [ ] **Step 1: Add the Vue mount point and printer buttons to the header**

In the existing header `<div class="btn-toolbar">` block (lines 6–13), add the Vue app mount point after the existing nav buttons:

```twig
    <div class="btn-toolbar">
        <a class="btn btn-primary me-2" href="/admin/predict?date={{prev.format('Y-m-d')}}">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <a class="btn btn-primary me-4" href="/admin/predict?date={{next.format('Y-m-d')}}">
            <i class="fa-solid fa-chevron-right"></i>
        </a>
        {% verbatim %}
        <div id="print-app">
            <template v-if="printers.length === 1">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    :disabled="printing"
                    @click="print(printers[0])"
                >
                    <i v-if="!printing" class="bi bi-printer"></i>
                    <span v-if="printing" class="spinner-border spinner-border-sm"></span>
                    <span class="d-none d-sm-inline ms-1">{{printers[0].name}}</span>
                </button>
            </template>
            <template v-else-if="printers.length > 1">
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        :disabled="printing"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <i v-if="!printing" class="bi bi-printer"></i>
                        <span v-if="printing" class="spinner-border spinner-border-sm"></span>
                        <i class="bi bi-chevron-down ms-1"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li v-for="printer in printers">
                            <a class="dropdown-item" href="#" @click.prevent="print(printer)">{{printer.name}}</a>
                        </li>
                    </ul>
                </div>
            </template>
        </div>
        {% endverbatim %}
    </div>
```

- [ ] **Step 2: Add hidden canvas before `{% endblock %}`**

Before the closing `{% endblock %}` at the end of the file, add:

```twig
<canvas width="576" height="10000" class="d-none"></canvas>
```

- [ ] **Step 3: Add the `{% block javascript %}` section**

Append the following after `{% endblock %}` at the end of the file:

```twig
{% block javascript %}
    {{ parent() }}
    <script>
        const printersData = {{ printersJson|raw }};
        const predictionData = {{ predictionJson|raw }};
    </script>
    <script src="/assets/js/vue.global.prod.js"></script>
    <script src="/assets/js/axios.min.js"></script>
    <script>
        const W = 576;
        const PAD = 6;
        let y = 0;

        const centerText = (ctx, text, fontSize, bold = false) => {
            ctx.font = `${bold ? 'bold ' : ''}${fontSize}px monospace`;
            ctx.textAlign = 'center';
            y += fontSize;
            ctx.fillText(text, W / 2, y);
            y += 6;
        };

        const leftText = (ctx, text, fontSize, bold = false) => {
            ctx.font = `${bold ? 'bold ' : ''}${fontSize}px monospace`;
            ctx.textAlign = 'left';
            y += fontSize;
            ctx.fillText(text, PAD, y);
            y += 6;
        };

        const separator = (ctx) => {
            y += 6;
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(W, y);
            ctx.stroke();
            y += 10;
        };
    </script>
    <script src="/assets/js/epos-2.24.0.js"></script>
    <script>
        Vue.createApp({
            data() {
                return {
                    printers: printersData,
                    printing: false,
                    printStatus: null,
                    canvasCtx: null,
                };
            },
            mounted() {
                this.canvasCtx = document.querySelector('canvas').getContext('2d');
            },
            methods: {
                buildReceiptXml() {
                    y = 0;
                    this.canvasCtx.clearRect(0, 0, W, 10000);
                    this.canvasCtx.fillStyle = 'white';
                    this.canvasCtx.fillRect(0, 0, W, 10000);
                    this.canvasCtx.fillStyle = 'black';

                    centerText(this.canvasCtx, 'ΠΡΟΒΛΕΨΗ', 40, true);
                    centerText(this.canvasCtx, predictionData.date, 30);
                    separator(this.canvasCtx);

                    for (const section of predictionData.menuSections) {
                        leftText(this.canvasCtx, section.name, 32, true);
                        for (const item of section.menuItems) {
                            const count = Math.round(item.count);
                            if (count === 0) continue;
                            leftText(this.canvasCtx, `${item.name}  x${count}`, 30);
                        }
                        separator(this.canvasCtx);
                    }

                    y += 12;

                    let pr = new epson.ePOSBuilder();
                    pr.addImage(this.canvasCtx, 0, 0, 576, y, pr.COLOR_1, pr.MODE_MONO);
                    pr.addFeedLine(3);
                    pr.addCut(pr.CUT_FEED);
                    return pr.toString();
                },
                async print(printer) {
                    if (this.printing) return;
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
                },
            },
        }).mount('#print-app');
    </script>
{% endblock %}
```

- [ ] **Step 4: Commit**

```bash
git add src/templates/admin/predict.twig
git commit -m "feat: add thermal print button to predict page"
```
