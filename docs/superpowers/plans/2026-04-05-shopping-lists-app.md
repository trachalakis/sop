# Shopping Lists App — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract the shopping lists feature from the admin section into a standalone `/shopping-lists-app` route group, following the same pattern as `orders-app` and `reservations-app`.

**Architecture:** Move both action classes to a new `ShoppingListsApp` namespace, move and adapt the Twig template to extend `app_skeleton.twig`, add a new route group with `Authentication` + `Authorization` middleware (no `Menus`), and remove all traces from the admin section. Update the unrun permissions migration to use the new paths.

**Tech Stack:** PHP 8, Slim 4, Twig 3, Vue.js 3 (frontend), Doctrine ORM, PostgreSQL. Dev environment via DDEV.

---

### Task 1: Create `UpdateShoppingList` action in `ShoppingListsApp` namespace

**Files:**
- Create: `src/Application/Actions/ShoppingListsApp/UpdateShoppingList.php`

- [ ] **Step 1: Create the new action class**

Create `src/Application/Actions/ShoppingListsApp/UpdateShoppingList.php` with this exact content (namespace changed from `Admin` to `ShoppingListsApp`, template path updated):

```php
<?php

declare(strict_types=1);

namespace Application\Actions\ShoppingListsApp;

use DateTimeImmutable;
use Domain\Repositories\PrintersRepository;
use Domain\Repositories\ShoppingListsRepository;
use Domain\Repositories\SupplyGroupsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateShoppingList
{
    public function __construct(
        private SupplyGroupsRepository $supplyGroupsRepository,
        private PrintersRepository $printersRepository,
        private ShoppingListsRepository $shoppingListsRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $supplyGroups = $this->supplyGroupsRepository->findBy([], ['position' => 'asc', 'name' => 'asc']);
        $printers = $this->printersRepository->findBy(['isActive' => true], ['name' => 'asc']);

        $supplyGroupsData = array_values(array_filter(array_map(function ($group) {
            $supplies = array_values(array_map(function ($supply) {
                return [
                    'id' => $supply->getId(),
                    'name' => $supply->getName(),
                    'priceUnit' => $supply->getPriceUnit(),
                    'price' => $supply->getPrice(),
                ];
            }, $group->getSupplies()->toArray()));

            if (count($supplies) === 0) {
                return null;
            }

            return [
                'name' => $group->getName(),
                'supplies' => $supplies,
            ];
        }, $supplyGroups)));

        $printersData = array_values(array_map(function ($printer) {
            return [
                'id' => $printer->getId(),
                'name' => $printer->getName(),
                'printerAddress' => $printer->getPrinterAddress(),
                'isReceiptPrinter' => $printer->getIsReceiptPrinter(),
            ];
        }, $printers));

        $targetDate = $this->getTargetDate();
        $shoppingList = $this->shoppingListsRepository->findByDate($targetDate);

        $existingEntries = [];
        $existingNotes = '';
        if ($shoppingList !== null) {
            foreach ($shoppingList->getEntries() as $entry) {
                $existingEntries[] = [
                    'supplyId' => $entry->getSupply()->getId(),
                    'quantity' => $entry->getQuantity(),
                ];
            }
            $existingNotes = $shoppingList->getNotes() ?? '';
        }

        return $this->twig->render(
            $response,
            'shopping_lists_app/update_shopping_list.twig',
            [
                'supplyGroupsJson' => json_encode($supplyGroupsData),
                'printersJson' => json_encode($printersData),
                'existingEntriesJson' => json_encode($existingEntries),
                'existingNotes' => $existingNotes,
                'targetDate' => $targetDate->format('Y-m-d'),
            ]
        );
    }

    private function getTargetDate(): DateTimeImmutable
    {
        $now = new DateTimeImmutable();
        if ((int) $now->format('H') < 5) {
            return $now;
        }
        return $now->modify('+1 day');
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Application/Actions/ShoppingListsApp/UpdateShoppingList.php
git commit -m "feat: add UpdateShoppingList action under ShoppingListsApp namespace"
```

---

### Task 2: Create `SaveShoppingList` action in `ShoppingListsApp` namespace

**Files:**
- Create: `src/Application/Actions/ShoppingListsApp/SaveShoppingList.php`

- [ ] **Step 1: Create the new action class**

Create `src/Application/Actions/ShoppingListsApp/SaveShoppingList.php` with this exact content (only the namespace changes):

```php
<?php

declare(strict_types=1);

namespace Application\Actions\ShoppingListsApp;

use DateTimeImmutable;
use Domain\Entities\ShoppingList;
use Domain\Entities\ShoppingListEntry;
use Domain\Repositories\ShoppingListsRepository;
use Domain\Repositories\SuppliesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SaveShoppingList
{
    public function __construct(
        private ShoppingListsRepository $shoppingListsRepository,
        private SuppliesRepository $suppliesRepository
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $entries = $body['entries'] ?? [];
        $notes = isset($body['notes']) ? trim((string) $body['notes']) : null;

        $targetDate = $this->getTargetDate();
        $dateStr = $targetDate->format('Y-m-d');

        $shoppingList = $this->shoppingListsRepository->findByDate($targetDate);
        $isNew = $shoppingList === null;

        if ($isNew) {
            $shoppingList = new ShoppingList();
            $shoppingList->setDate(new DateTimeImmutable($dateStr));
            $shoppingList->setCreatedAt(new DateTimeImmutable());
        }

        $shoppingList->setUpdatedAt(new DateTimeImmutable());
        $shoppingList->setNotes($notes !== '' ? $notes : null);
        $shoppingList->clearEntries();

        foreach ($entries as $entryData) {
            $supply = $this->suppliesRepository->find((int) $entryData['supplyId']);
            if ($supply === null) {
                continue;
            }

            $entry = new ShoppingListEntry();
            $entry->setShoppingList($shoppingList);
            $entry->setSupply($supply);
            $entry->setQuantity((float) $entryData['quantity']);
            $entry->setUnitCost(isset($entryData['unitCost']) ? (float) $entryData['unitCost'] : null);
            $shoppingList->addEntry($entry);
        }

        $this->shoppingListsRepository->persist($shoppingList);

        $response->getBody()->write(json_encode([
            'success' => true,
            'date' => $dateStr,
            'isNew' => $isNew,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getTargetDate(): DateTimeImmutable
    {
        $now = new DateTimeImmutable();
        if ((int) $now->format('H') < 5) {
            return $now;
        }
        return $now->modify('+1 day');
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Application/Actions/ShoppingListsApp/SaveShoppingList.php
git commit -m "feat: add SaveShoppingList action under ShoppingListsApp namespace"
```

---

### Task 3: Create the new template

**Files:**
- Create: `src/templates/shopping_lists_app/update_shopping_list.twig`

- [ ] **Step 1: Create the template directory and file**

Create `src/templates/shopping_lists_app/update_shopping_list.twig`. This is identical to the admin version with three changes:
1. Line 1: `{% extends 'app_skeleton.twig' %}` (was `admin/skeleton.twig`)
2. Line 3: Remove `{% set activeNavLink = 'shopping-lists' %}` entirely
3. Line 13: breadcrumb link href changes to `/shopping-lists-app/update` (was `/admin/shopping-lists/update`)
4. Line 279: `axios.post` URL changes to `/shopping-lists-app/save` (was `/admin/shopping-lists/save`)

Full file content:

```twig
{% extends 'app_skeleton.twig' %}

{% block content %}{% verbatim %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/shopping-lists-app/update" class="text-decoration-none">Ψώνια</a>
    </li>
</ol>
<div id="app">

    <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
        <div>
            <h1 class="mb-0">Ψώνια</h1>
            <small class="text-muted">για {{formatDate(targetDate)}}<span v-if="selectedSupplies.length > 0"> &mdash; &euro;{{totalCost}}</span></small>
        </div>
        <div class="d-flex gap-2 align-items-center">

            <button
                type="button"
                class="btn btn-lg"
                :class="saveStatus === 'saved' ? 'btn-primary' : (saveStatus === 'error' ? 'btn-danger' : 'btn-outline-primary')"
                :disabled="selectedSupplies.length === 0 || saving"
                @click="save"
                title="Αποθήκευση λίστας"
            >
                <i v-if="!saving" class="bi" :class="saveStatus === 'saved' ? 'bi-check-lg' : 'bi-floppy'"></i>
                <span v-if="saving" class="spinner-border spinner-border-sm"></span>
            </button>

            <template v-if="receiptPrinters.length === 1">
                <button
                    type="button"
                    class="btn btn-lg"
                    :class="selectedSupplies.length > 0 ? 'btn-outline-primary' : 'btn-secondary'"
                    :disabled="selectedSupplies.length === 0 || printing"
                    @click="print(receiptPrinters[0])"
                >
                    <i class="bi bi-printer"></i>
                    <span class="d-none d-sm-inline ms-1">{{receiptPrinters[0].name}}</span>
                </button>
            </template>
            <template v-else-if="receiptPrinters.length > 1">
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-lg"
                        :class="selectedSupplies.length > 0 ? 'btn-primary' : 'btn-secondary'"
                        :disabled="selectedSupplies.length === 0 || printing"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <i class="bi bi-printer"></i>
                        <span v-if="selectedSupplies.length > 0" class="badge bg-light text-dark ms-1">@{{selectedSupplies.length}}</span>
                        <i class="bi bi-chevron-down ms-1"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li v-for="printer in receiptPrinters">
                            <a class="dropdown-item fs-5 py-2" href="#" @click.prevent="print(printer)">@{{printer.name}}</a>
                        </li>
                    </ul>
                </div>
            </template>

            <button
                v-if="selectedSupplies.length > 0"
                type="button"
                class="btn btn-lg btn-outline-secondary"
                @click="clearSelection"
                title="Εκκαθάριση"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>
    </div>

    <div
        v-if="saveStatus === 'saved'"
        class="alert alert-success alert-dismissible py-2 mb-3"
        role="alert"
    >
        <i class="bi bi-check-circle me-1"></i>
        Η λίστα αποθηκεύτηκε για {{formatDate(targetDate)}}.
        <button type="button" class="btn-close" @click="saveStatus = null"></button>
    </div>

    <div
        v-if="printStatus === 'printed'"
        class="alert alert-success alert-dismissible py-2 mb-3"
        role="alert"
    >
        <i class="bi bi-printer me-1"></i>
        Η λίστα στάλθηκε στον εκτυπωτή.
        <button type="button" class="btn-close" @click="printStatus = null"></button>
    </div>

    <div class="mb-4">
        <textarea
            class="form-control"
            rows="2"
            placeholder="Σημειώσεις..."
            v-model="notes"
        ></textarea>
    </div>

    <div v-for="group in supplyGroups" :key="group.name" class="mb-4">
        <h5 class="text-muted fw-semibold mb-2 text-uppercase">{{group.name}}</h5>
        <div class="row g-2">
            <div
                v-for="supply in group.supplies"
                :key="supply.id"
                class="col-6 col-sm-4 col-md-3 col-lg-2"
            >
                <div
                    class="card h-100 user-select-none"
                    :class="isSelected(supply) ? 'border-primary shadow-sm' : 'border'"
                    style="cursor: pointer;"
                    @click="toggleSupply(supply)"
                >
                    <div class="card-body p-2 d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 72px;">
                        <div
                            class="fw-medium"
                            :class="isSelected(supply) ? 'text-primary' : ''"
                        >
                            {{supply.name}}
                        </div>
                        <div class="input-group mt-2" v-if="isSelected(supply)" @click.stop>
                            <input
                                type="number"
                                class="form-control form-control-sm text-center"
                                :value="getSelection(supply).quantity"
                                @input="setQuantity(supply, $event.target.value)"
                                min="0.1"
                                step="0.5"
                                
                            />
                            <span class="input-group-text">{{supply.priceUnit}}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="supplyGroups.length === 0" class="text-center text-muted py-5">
        Δεν υπάρχουν προμήθειες.
    </div>

    <canvas width="576" height="5000" class="d-none"></canvas>

</div>
{% endverbatim %}
{% endblock %}

{% block javascript %}
    {{ parent() }}
    <script>
        const supplyGroupsData = {{ supplyGroupsJson|raw }};
        const printersData = {{ printersJson|raw }};
        const existingEntriesData = {{ existingEntriesJson|raw }};
        const existingNotesData = {{ existingNotes|json_encode|raw }};
        const targetDateStr = "{{ targetDate }}";
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
                    supplyGroups: supplyGroupsData,
                    printers: printersData,
                    targetDate: targetDateStr,
                    selectedSupplies: [],
                    notes: existingNotesData,
                    printing: false,
                    saving: false,
                    saveStatus: null,
                    printStatus: null,
                    canvasCtx: null,
                };
            },
            mounted() {
                this.canvasCtx = document.querySelector('canvas').getContext('2d');

                existingEntriesData.forEach(entry => {
                    for (const group of this.supplyGroups) {
                        const supply = group.supplies.find(s => s.id === entry.supplyId);
                        if (supply) {
                            this.selectedSupplies.push({ ...supply, quantity: entry.quantity });
                            break;
                        }
                    }
                });
            },
            computed: {
                receiptPrinters() {
                    return this.printers.filter(p => p.isReceiptPrinter);
                },
                totalCost() {
                    const total = this.selectedSupplies.reduce((sum, s) => sum + (s.price * s.quantity), 0);
                    return Math.round(total * 100) / 100;
                },
            },
            methods: {
                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const [y, m, d] = dateStr.split('-');
                    return `${d}/${m}/${y}`;
                },
                isSelected(supply) {
                    return this.selectedSupplies.some(s => s.id === supply.id);
                },
                getSelection(supply) {
                    return this.selectedSupplies.find(s => s.id === supply.id);
                },
                toggleSupply(supply) {
                    const idx = this.selectedSupplies.findIndex(s => s.id === supply.id);
                    if (idx === -1) {
                        this.selectedSupplies.push({ ...supply, quantity: 1 });
                    } else {
                        this.selectedSupplies.splice(idx, 1);
                    }
                },
                setQuantity(supply, value) {
                    const sel = this.getSelection(supply);
                    if (sel) {
                        sel.quantity = parseFloat(value) || 0;
                    }
                },
                clearSelection() {
                    this.selectedSupplies = [];
                    this.saveStatus = null;
                },
                async save() {
                    if (this.saving || this.selectedSupplies.length === 0) return;
                    this.saving = true;
                    this.saveStatus = null;
                    try {
                        await axios.post('/shopping-lists-app/save', {
                            notes: this.notes,
                            entries: this.selectedSupplies.map(s => ({
                                supplyId: s.id,
                                quantity: s.quantity,
                                unitCost: s.price,
                            })),
                        });
                        this.saveStatus = 'saved';
                    } catch (error) {
                        this.saveStatus = 'error';
                        alert('Σφάλμα αποθήκευσης: ' + error);
                    } finally {
                        this.saving = false;
                    }
                },
                getHm() {
                    const now = new Date();
                    const h = now.getHours();
                    const m = now.getMinutes();
                    return `${h}:${m < 10 ? '0' + m : m}`;
                },
                buildReceiptXml() {
                    y = 0;
                    this.canvasCtx.clearRect(0, 0, W, 5000);
                    this.canvasCtx.fillStyle = 'white';
                    this.canvasCtx.fillRect(0, 0, W, 5000);
                    this.canvasCtx.fillStyle = 'black';

                    const [yr, mo, da] = this.targetDate.split('-');
                    const dateLabel = `${da}/${mo}/${yr}  ${this.getHm()}`;

                    centerText(this.canvasCtx, 'ΛΙΣΤΑ ΠΡΟΜΗΘΕΙΩΝ', 32, true);
                    centerText(this.canvasCtx, dateLabel, 24);
                    if (this.notes && this.notes.trim().length > 0) {
                        separator(this.canvasCtx);
                        leftText(this.canvasCtx, this.notes.trim(), 24);
                    }
                    separator(this.canvasCtx);
                    y += 36;

                    const LINE_SIZE = 28;
                    const MAX_CHARS = 31;

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
                    const totalStr = `ΣΥΝΟΛΟ: €${(Math.round(total * 100) / 100).toFixed(2)}`;
                    leftText(this.canvasCtx, totalStr, LINE_SIZE, true);

                    y += 12;

                    let ePOSBuilder = new epson.ePOSBuilder();
                    ePOSBuilder.addImage(this.canvasCtx, 0, 0, 576, y, ePOSBuilder.COLOR_1, ePOSBuilder.MODE_MONO);
                    ePOSBuilder.addFeedLine(3);
                    ePOSBuilder.addCut(ePOSBuilder.CUT_FEED);
                    return ePOSBuilder.toString();
                },
                async print(printer) {
                    if (this.printing || this.selectedSupplies.length === 0) return;
                    this.printing = true;
                    this.printStatus = null;
                    try {
                        const xml = this.buildReceiptXml();
                        await axios.post('/admin/print-jobs/create', {
                            printer: printer.name,
                            xml,
                        });
                        this.printStatus = 'printed';
                    } catch (error) {
                        this.printStatus = 'error';
                        alert('Σφάλμα εκτύπωσης: ' + error);
                    } finally {
                        this.printing = false;
                    }
                },
            },
        }).mount('#app');
    </script>
{% endblock %}
```

- [ ] **Step 2: Commit**

```bash
git add src/templates/shopping_lists_app/update_shopping_list.twig
git commit -m "feat: add shopping_lists_app template extending app_skeleton"
```

---

### Task 4: Update `app/routes.php`

**Files:**
- Modify: `app/routes.php`

- [ ] **Step 1: Replace the two `use` imports for the Admin shopping list classes**

In `app/routes.php`, replace:

```php
use Application\Actions\Admin\SaveShoppingList;
```
with:
```php
use Application\Actions\ShoppingListsApp\SaveShoppingList as ShoppingListsAppSaveShoppingList;
```

And replace:
```php
use Application\Actions\Admin\UpdateShoppingList;
```
with:
```php
use Application\Actions\ShoppingListsApp\UpdateShoppingList as ShoppingListsAppUpdateShoppingList;
```

- [ ] **Step 2: Remove the two routes from the admin group**

In `app/routes.php`, remove these two lines (they are inside the `/admin` group around line 192-193):

```php
        $group->get('/shopping-lists/update', UpdateShoppingList::class);
        $group->post('/shopping-lists/save', SaveShoppingList::class);
```

- [ ] **Step 3: Add the new `/shopping-lists-app` route group**

After the `/reservations-app` group (around line 245) and before the `/users-app` group, add:

```php
    $app->group('/shopping-lists-app', function (RouteCollectorProxy $group) {
        $group->get('/update', ShoppingListsAppUpdateShoppingList::class);
        $group->post('/save', ShoppingListsAppSaveShoppingList::class);
    })
    ->add(new Authorization($container->get(UserPermissionsRepository::class)))
    ->add(new Authentication());
```

- [ ] **Step 4: Verify the DDEV environment starts without errors**

```bash
ddev exec php -r "require 'vendor/autoload.php'; echo 'OK';"
```

Expected: `OK` (no PHP parse errors).

- [ ] **Step 5: Commit**

```bash
git add app/routes.php
git commit -m "feat: add /shopping-lists-app route group, remove from /admin"
```

---

### Task 5: Remove the shopping lists nav link from admin skeleton and delete old files

**Files:**
- Modify: `src/templates/admin/skeleton.twig`
- Delete: `src/Application/Actions/Admin/UpdateShoppingList.php`
- Delete: `src/Application/Actions/Admin/SaveShoppingList.php`
- Delete: `src/templates/admin/update_shopping_list.twig`

- [ ] **Step 1: Remove the shopping lists `<li>` from the admin nav**

In `src/templates/admin/skeleton.twig`, remove these lines (around lines 68-72):

```twig
					<li class="nav-item">
						<a class="nav-link {{activeNavLink == 'shopping-lists' ? 'active' : ''}}" href="/admin/shopping-lists/update">
							Ψώνια
						</a>
					</li>
```

- [ ] **Step 2: Delete the old admin action classes and template**

```bash
rm src/Application/Actions/Admin/UpdateShoppingList.php
rm src/Application/Actions/Admin/SaveShoppingList.php
rm src/templates/admin/update_shopping_list.twig
```

- [ ] **Step 3: Commit**

```bash
git add -u
git commit -m "chore: remove shopping lists from admin section"
```

---

### Task 6: Update the permissions migration

**Files:**
- Modify: `migrations/add_shopping_lists_manager_permissions.php`

- [ ] **Step 1: Update the paths array**

In `migrations/add_shopping_lists_manager_permissions.php`, the `$paths` array currently contains the old admin paths. Replace it with:

```php
$paths = [
    'shopping-lists-app/update',
    'shopping-lists-app/save',
];
```

- [ ] **Step 2: Run the migration**

```bash
ddev exec php migrations/add_shopping_lists_manager_permissions.php
```

Expected output:
```
Connected to database 'db'.
Upserted permission for 'shopping-lists-app/update'.
Upserted permission for 'shopping-lists-app/save'.
Migration completed successfully.
```

- [ ] **Step 3: Commit**

```bash
git add migrations/add_shopping_lists_manager_permissions.php
git commit -m "feat: update shopping lists permissions to new app paths"
```

---

### Task 7: Manual smoke test

- [ ] **Step 1: Visit the new URL as a webmaster**

Open `https://sop.ddev.site/shopping-lists-app/update` in a browser while logged in as a webmaster.

Expected: The page loads with the `app_skeleton.twig` layout (no admin sidebar), supply group cards are visible, and the save button is present.

- [ ] **Step 2: Verify the save POST goes to the right URL**

Open browser DevTools → Network tab. Select a supply and click the save button.

Expected: A POST request to `/shopping-lists-app/save` returns `{"success":true,...}`.

- [ ] **Step 3: Verify the old admin URL is gone**

Visit `https://sop.ddev.site/admin/shopping-lists/update`.

Expected: 404 or redirect to login/forbidden (not a working page).

- [ ] **Step 4: Verify the admin nav no longer shows "Ψώνια"**

Visit `https://sop.ddev.site/admin/` while logged in.

Expected: The sidebar nav does not contain a "Ψώνια" link.
