# Supplier Entity & Admin CRUD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Introduce a first-class `Supplier` entity, migrate existing supplier names from the `supplies.custom_fields` JSON into it, wire supplies to their supplier via a FK, and provide a full admin CRUD.

**Architecture:** Follow the existing Doctrine ORM + Slim 4 action pattern throughout. A standalone PDO migration script handles the data move. The `Supply` entity gains a nullable `ManyToOne` to `Supplier`. Four new action classes and three Twig templates implement the CRUD. The supply create/update forms gain a supplier dropdown.

**Tech Stack:** PHP 8, Doctrine ORM 3, Slim 4, Twig 3, Bootstrap 5, PostgreSQL, PHP-DI 7

---

### Task 1: `Supplier` entity + `SuppliersRepository`

**Files:**
- Create: `src/Domain/Entities/Supplier.php`
- Create: `src/Domain/Repositories/SuppliersRepository.php`

- [ ] **Step 1: Create the `Supplier` entity**

```php
<?php
// src/Domain/Entities/Supplier.php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\SuppliersRepository;

#[ORM\Entity(repositoryClass: SuppliersRepository::class)]
#[ORM\Table(name: 'suppliers')]
class Supplier
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string', unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $telephone = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setTelephone(?string $telephone): void
    {
        $this->telephone = $telephone;
    }
}
```

- [ ] **Step 2: Create `SuppliersRepository`**

```php
<?php
// src/Domain/Repositories/SuppliersRepository.php

declare(strict_types=1);

namespace Domain\Repositories;

use Doctrine\ORM\EntityRepository;
use Domain\Entities\Supplier;

class SuppliersRepository extends EntityRepository
{
    public function persist(Supplier $supplier): void
    {
        $this->getEntityManager()->persist($supplier);
        $this->getEntityManager()->flush();
    }

    public function delete(Supplier $supplier): void
    {
        $this->getEntityManager()->remove($supplier);
        $this->getEntityManager()->flush();
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Domain/Entities/Supplier.php src/Domain/Repositories/SuppliersRepository.php
git commit -m "feat: add Supplier entity and SuppliersRepository"
```

---

### Task 2: Add `supplier` relation to `Supply` entity

**Files:**
- Modify: `src/Domain/Entities/Supply.php`

- [ ] **Step 1: Add the import and the relation property**

At the top of `src/Domain/Entities/Supply.php`, add to the existing imports:
```php
use Domain\Entities\Supplier;
```

After the `$priceHistory` property (around line 44), add:
```php
    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(name: 'supplier_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Supplier $supplier = null;
```

- [ ] **Step 2: Add getter and setter**

After `getSupplyGroup()` (around line 93), add:
```php
    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }
```

- [ ] **Step 3: Commit**

```bash
git add src/Domain/Entities/Supply.php
git commit -m "feat: add supplier relation to Supply entity"
```

---

### Task 3: Migration script

**Files:**
- Create: `migrations/create_suppliers_and_migrate_supply_data.php`

This script runs via `ddev exec php migrations/create_suppliers_and_migrate_supply_data.php`. It creates the `suppliers` table, populates it from the `custom_fields` JSON, adds `supplier_id` FK to `supplies`, sets it per row, and removes the `"supplier"` key from `custom_fields`.

- [ ] **Step 1: Create the migration script**

```php
<?php
// migrations/create_suppliers_and_migrate_supply_data.php

declare(strict_types=1);

$envFile = __DIR__ . '/../app/.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found at {$envFile}\n");
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
}

$dsn = "pgsql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}";

try {
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "Connected to database '{$_ENV['DB_NAME']}'.\n";

// Guard: check if already migrated
$exists = $pdo->query("SELECT to_regclass('public.suppliers')")->fetchColumn();
if ($exists) {
    die("Table 'suppliers' already exists. Migration may have already run.\n");
}

$pdo->beginTransaction();

try {
    // 1. Create suppliers table
    $pdo->exec("
        CREATE TABLE suppliers (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            telephone VARCHAR(255) DEFAULT NULL
        )
    ");
    echo "Created table 'suppliers'.\n";

    // 2. Insert unique supplier names from custom_fields
    $rows = $pdo->query("
        SELECT DISTINCT custom_fields->>'supplier' AS supplier_name
        FROM supplies
        WHERE custom_fields->>'supplier' IS NOT NULL
          AND custom_fields->>'supplier' != ''
    ")->fetchAll(PDO::FETCH_COLUMN);

    $insert = $pdo->prepare("INSERT INTO suppliers (name) VALUES (:name)");
    foreach ($rows as $name) {
        $insert->execute([':name' => $name]);
    }
    echo "Inserted " . count($rows) . " supplier(s).\n";

    // 3. Add supplier_id FK column to supplies
    $pdo->exec("
        ALTER TABLE supplies
        ADD COLUMN supplier_id INTEGER REFERENCES suppliers(id) ON DELETE SET NULL
    ");
    echo "Added 'supplier_id' column to 'supplies'.\n";

    // 4. Set supplier_id on each supply
    $pdo->exec("
        UPDATE supplies
        SET supplier_id = (
            SELECT id FROM suppliers
            WHERE name = custom_fields->>'supplier'
        )
        WHERE custom_fields->>'supplier' IS NOT NULL
          AND custom_fields->>'supplier' != ''
    ");
    echo "Linked supplies to their suppliers.\n";

    // 5. Remove 'supplier' key from custom_fields JSON in PHP
    $supplyRows = $pdo->query("SELECT id, custom_fields FROM supplies")->fetchAll(PDO::FETCH_ASSOC);
    $update = $pdo->prepare("UPDATE supplies SET custom_fields = :json WHERE id = :id");
    foreach ($supplyRows as $row) {
        $data = json_decode($row['custom_fields'], true) ?? [];
        unset($data['supplier']);
        $update->execute([':json' => json_encode($data), ':id' => $row['id']]);
    }
    echo "Removed 'supplier' key from custom_fields on " . count($supplyRows) . " supply row(s).\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (Throwable $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
```

- [ ] **Step 2: Run the migration**

```bash
ddev exec php migrations/create_suppliers_and_migrate_supply_data.php
```

Expected output:
```
Connected to database 'db'.
Created table 'suppliers'.
Inserted N supplier(s).
Added 'supplier_id' column to 'supplies'.
Linked supplies to their suppliers.
Removed 'supplier' key from custom_fields on M supply row(s).
Migration completed successfully.
```

- [ ] **Step 3: Commit**

```bash
git add migrations/create_suppliers_and_migrate_supply_data.php
git commit -m "feat: add migration to create suppliers table and migrate supply data"
```

---

### Task 4: DI binding + routes + sidebar nav

**Files:**
- Modify: `app/dependencies.php`
- Modify: `app/routes.php`
- Modify: `src/templates/admin/skeleton.twig`

- [ ] **Step 1: Add `SuppliersRepository` to DI**

In `app/dependencies.php`, add these two imports near the top with the other entity/repository imports:
```php
use Domain\Entities\Supplier;
use Domain\Repositories\SuppliersRepository;
```

Then add the binding after the `SupplyGroupsRepository` binding (around line 224):
```php
        SuppliersRepository::class => function (ContainerInterface $c) {
            $em = $c->get(EntityManager::class);
            return $em->getRepository(Supplier::class);
        },
```

- [ ] **Step 2: Add routes**

In `app/routes.php`, add these four use statements near the top with the other Admin action imports:
```php
use Application\Actions\Admin\Suppliers;
use Application\Actions\Admin\CreateSupplier;
use Application\Actions\Admin\UpdateSupplier;
use Application\Actions\Admin\DeleteSupplier;
```

Then add the four routes inside the admin `$group` block, after the supplies routes (around line 199):
```php
        $group->get('/suppliers', Suppliers::class);
        $group->map(['GET', 'POST'], '/suppliers/create', CreateSupplier::class);
        $group->map(['GET', 'POST'], '/suppliers/update', UpdateSupplier::class);
        $group->get('/suppliers/delete', DeleteSupplier::class);
```

- [ ] **Step 3: Add sidebar nav entry**

In `src/templates/admin/skeleton.twig`, after the "Προμήθειες" `<li>` block (around line 72), add:
```twig
						<li class="nav-item">
							<a class="nav-link {{activeNavLink == 'suppliers' ? 'active' : ''}}" href="/admin/suppliers">
								Προμηθευτές
							</a>
						</li>
```

- [ ] **Step 4: Commit**

```bash
git add app/dependencies.php app/routes.php src/templates/admin/skeleton.twig
git commit -m "feat: register SuppliersRepository, routes, and sidebar nav for suppliers"
```

---

### Task 5: `Suppliers` list action + template

**Files:**
- Create: `src/Application/Actions/Admin/Suppliers.php`
- Create: `src/templates/admin/suppliers.twig`

- [ ] **Step 1: Create the action**

```php
<?php
// src/Application/Actions/Admin/Suppliers.php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Suppliers
{
    public function __construct(
        private SuppliersRepository $suppliersRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $suppliers = $this->suppliersRepository->findBy([], ['name' => 'ASC']);
        return $this->twig->render($response, 'admin/suppliers.twig', [
            'suppliers' => $suppliers,
        ]);
    }
}
```

- [ ] **Step 2: Create the template**

```twig
{# src/templates/admin/suppliers.twig #}
{% extends 'admin/skeleton.twig' %}

{% set activeNavLink = 'suppliers' %}

{% block content %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/admin/" class="text-decoration-none">
            <i class="bi bi-house"></i>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="/admin/suppliers" class="text-decoration-none">Προμηθευτές</a>
    </li>
</ol>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-1 mb-3 border-bottom">
    <h1>Προμηθευτές</h1>
    <div class="btn-toolbar gap-2">
        <a href="/admin/suppliers/create" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i>
        </a>
    </div>
</div>
<table class="table">
    <thead>
        <tr>
            <th>Όνομα</th>
            <th>Τηλέφωνο</th>
        </tr>
    </thead>
    <tbody>
        {% for supplier in suppliers %}
        <tr>
            <td>
                <a class="text-decoration-none" href="/admin/suppliers/update?id={{supplier.getId}}">
                    {{supplier.getName}}
                </a>
            </td>
            <td>{{supplier.getTelephone}}</td>
        </tr>
        {% endfor %}
    </tbody>
</table>
{% endblock %}
```

- [ ] **Step 3: Verify**

Open `https://sop.ddev.site/admin/suppliers` — should show a table of migrated suppliers with their names. "Προμηθευτές" should appear in the sidebar and be highlighted.

- [ ] **Step 4: Commit**

```bash
git add src/Application/Actions/Admin/Suppliers.php src/templates/admin/suppliers.twig
git commit -m "feat: add suppliers list action and template"
```

---

### Task 6: `CreateSupplier` action + template

**Files:**
- Create: `src/Application/Actions/Admin/CreateSupplier.php`
- Create: `src/templates/admin/create_supplier.twig`

- [ ] **Step 1: Create the action**

```php
<?php
// src/Application/Actions/Admin/CreateSupplier.php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Supplier;
use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateSupplier
{
    public function __construct(
        private SuppliersRepository $suppliersRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();
            $supplier = new Supplier();
            $supplier->setName($data['name']);
            $supplier->setTelephone($data['telephone'] !== '' ? $data['telephone'] : null);
            $this->suppliersRepository->persist($supplier);
            return $response->withHeader('Location', '/admin/suppliers')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/create_supplier.twig', []);
    }
}
```

- [ ] **Step 2: Create the template**

```twig
{# src/templates/admin/create_supplier.twig #}
{% extends 'admin/skeleton.twig' %}

{% set activeNavLink = 'suppliers' %}

{% block content %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/admin/" class="text-decoration-none">
            <i class="bi bi-house"></i>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="/admin/suppliers" class="text-decoration-none">Προμηθευτές</a>
    </li>
</ol>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-1 mb-3 border-bottom">
    <h1 class="h2">Δημιουργία</h1>
</div>
<form method="post" autocomplete="off" action="">
    <div class="row mb-3">
        <label class="col-sm-2 col-form-label">Όνομα</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" name="name" required/>
        </div>
    </div>
    <div class="row mb-3">
        <label class="col-sm-2 col-form-label">Τηλέφωνο</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" name="telephone"/>
        </div>
    </div>
    <hr/>
    <div class="row mb-3">
        <div class="col-sm-10 offset-sm-2">
            <button type="submit" class="btn btn-primary">Αποθήκευση</button>
        </div>
    </div>
</form>
{% endblock %}
```

- [ ] **Step 3: Verify**

Open `https://sop.ddev.site/admin/suppliers/create`, fill in a name and telephone, submit. Should redirect to `/admin/suppliers` and show the new supplier.

- [ ] **Step 4: Commit**

```bash
git add src/Application/Actions/Admin/CreateSupplier.php src/templates/admin/create_supplier.twig
git commit -m "feat: add create supplier action and template"
```

---

### Task 7: `UpdateSupplier` action + template

**Files:**
- Create: `src/Application/Actions/Admin/UpdateSupplier.php`
- Create: `src/templates/admin/update_supplier.twig`

- [ ] **Step 1: Create the action**

```php
<?php
// src/Application/Actions/Admin/UpdateSupplier.php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateSupplier
{
    public function __construct(
        private SuppliersRepository $suppliersRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $supplier = $this->suppliersRepository->find($request->getQueryParams()['id']);

        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();
            $supplier->setName($data['name']);
            $supplier->setTelephone($data['telephone'] !== '' ? $data['telephone'] : null);
            $this->suppliersRepository->persist($supplier);
            return $response->withHeader('Location', '/admin/suppliers')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/update_supplier.twig', [
            'supplier' => $supplier,
        ]);
    }
}
```

- [ ] **Step 2: Create the template**

```twig
{# src/templates/admin/update_supplier.twig #}
{% extends 'admin/skeleton.twig' %}

{% set activeNavLink = 'suppliers' %}

{% block content %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/admin/" class="text-decoration-none">
            <i class="bi bi-house"></i>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="/admin/suppliers" class="text-decoration-none">Προμηθευτές</a>
    </li>
</ol>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-1 mb-3 border-bottom">
    <h1 class="h2">{{supplier.getName}}</h1>
    <div class="btn-toolbar">
        <a href="/admin/suppliers/delete?id={{supplier.getId}}" class="btn btn-danger confirm">
            <i class="bi bi-trash"></i>
        </a>
    </div>
</div>
{% if exception is defined and exception is not null %}
    <div class="alert alert-danger" role="alert">
        {{exception.getMessage}}
    </div>
{% endif %}
<form method="post" autocomplete="off" action="">
    <div class="row mb-3">
        <label class="col-sm-2 col-form-label">Όνομα</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" name="name" value="{{supplier.getName}}" required/>
        </div>
    </div>
    <div class="row mb-3">
        <label class="col-sm-2 col-form-label">Τηλέφωνο</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" name="telephone" value="{{supplier.getTelephone}}"/>
        </div>
    </div>
    <hr/>
    <div class="row mb-3">
        <div class="col-sm-10 offset-sm-2">
            <button type="submit" class="btn btn-primary">Αποθήκευση</button>
        </div>
    </div>
</form>
{% endblock %}
```

- [ ] **Step 3: Verify**

Click a supplier from the list, edit name or telephone, save. Should redirect back to the list with updated data. The delete button should show a confirmation dialog (bootbox, same as other entities).

- [ ] **Step 4: Commit**

```bash
git add src/Application/Actions/Admin/UpdateSupplier.php src/templates/admin/update_supplier.twig
git commit -m "feat: add update supplier action and template"
```

---

### Task 8: `DeleteSupplier` action

**Files:**
- Create: `src/Application/Actions/Admin/DeleteSupplier.php`

The `supplier_id` FK uses `ON DELETE SET NULL`, so deleting a supplier automatically nullifies the FK on any linked supplies — no FK violation to catch.

- [ ] **Step 1: Create the action**

```php
<?php
// src/Application/Actions/Admin/DeleteSupplier.php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteSupplier
{
    public function __construct(
        private SuppliersRepository $suppliersRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $supplier = $this->suppliersRepository->find($request->getQueryParams()['id']);
        $this->suppliersRepository->delete($supplier);
        return $response->withHeader('Location', '/admin/suppliers')->withStatus(302);
    }
}
```

- [ ] **Step 2: Verify**

Open a supplier's update page, click the delete button, confirm. Should redirect to `/admin/suppliers` and the supplier should no longer appear.

- [ ] **Step 3: Commit**

```bash
git add src/Application/Actions/Admin/DeleteSupplier.php
git commit -m "feat: add delete supplier action"
```

---

### Task 9: Supply forms — add supplier dropdown

**Files:**
- Modify: `src/Application/Actions/Admin/CreateSupply.php`
- Modify: `src/Application/Actions/Admin/UpdateSupply.php`
- Modify: `src/templates/admin/create_supply.twig`
- Modify: `src/templates/admin/update_supply.twig`

- [ ] **Step 1: Update `CreateSupply` action**

In `src/Application/Actions/Admin/CreateSupply.php`:

Add to imports:
```php
use Domain\Repositories\SuppliersRepository;
```

Add `SuppliersRepository` to the constructor:
```php
    public function __construct(
    	private SuppliesRepository $suppliesRepository,
    	private SupplyGroupsRepository $supplyGroupsRepository,
    	private SuppliersRepository $suppliersRepository,
    	private Twig $twig
    ) {
    }
```

In the POST block, after `$supply->setVatRate(...)`, add:
```php
            $supplierId = $requestData['supplier'] ?? '';
            $supply->setSupplier($supplierId !== '' ? $this->suppliersRepository->find((int)$supplierId) : null);
```

In the GET block, add `suppliers` to the render data:
```php
    	$supplyGroups = $this->supplyGroupsRepository->findBy([], ['name' => 'asc']);
    	$suppliers = $this->suppliersRepository->findBy([], ['name' => 'ASC']);
    	return $this->twig->render(
    		$response,
    		'admin/create_supply.twig',
    		[
    			'supplyGroups' => $supplyGroups,
                'priceUnits' => PriceUnit::cases(),
                'suppliers' => $suppliers,
    		]
    	);
```

- [ ] **Step 2: Update `UpdateSupply` action**

In `src/Application/Actions/Admin/UpdateSupply.php`:

Add to imports:
```php
use Domain\Repositories\SuppliersRepository;
```

Add `SuppliersRepository` to the constructor:
```php
    public function __construct(
    	private SuppliesRepository $suppliesRepository,
    	private SupplyGroupsRepository $supplyGroupsRepository,
    	private SupplyPriceHistoryRepository $supplyPriceHistoryRepository,
    	private SuppliersRepository $suppliersRepository,
    	private Twig $twig
    ) {
    }
```

In the POST block, after `$supply->setCustomFields([])` block, add:
```php
            $supplierId = $requestData['supplier'] ?? '';
            $supply->setSupplier($supplierId !== '' ? $this->suppliersRepository->find((int)$supplierId) : null);
```

In the GET block, add `suppliers` to the render data:
```php
    	$supplyGroups = $this->supplyGroupsRepository->findBy([], ['name' => 'asc']);
    	$suppliers = $this->suppliersRepository->findBy([], ['name' => 'ASC']);

        $priceHistory = $supply->getPriceHistory()->toArray();
        usort($priceHistory, fn($a, $b) => $a->getValidFrom() <=> $b->getValidFrom());

    	return $this->twig->render(
    		$response,
    		'admin/update_supply.twig',
    		[
    			'supply' => $supply,
    			'supplyGroups' => $supplyGroups,
                'priceUnits' => PriceUnit::cases(),
                'priceHistory' => $priceHistory,
                'suppliers' => $suppliers,
    		]
    	);
```

- [ ] **Step 3: Add supplier dropdown to `create_supply.twig`**

In `src/templates/admin/create_supply.twig`, add the following block after the ΦΠΑ field (before `<hr/>`):
```twig
	<div class="row mb-3">
		<label class="col-sm-2 col-form-label">Προμηθευτής</label>
		<div class="col-sm-10">
			<select class="form-select" name="supplier">
				<option value="">—</option>
				{% for supplier in suppliers %}
					<option value="{{supplier.getId}}">{{supplier.getName}}</option>
				{% endfor %}
			</select>
		</div>
	</div>
```

- [ ] **Step 4: Add supplier dropdown to `update_supply.twig`**

In `src/templates/admin/update_supply.twig`, add the following block after the ΦΠΑ field (before `<div class="row mb-3">` for "Πρόσθετα πεδία"):
```twig
            <div class="row mb-3">
                <label class="col-sm-2 col-form-label">Προμηθευτής</label>
                <div class="col-sm-10">
                    <select class="form-select" name="supplier">
                        <option value="">—</option>
                        {% for supplier in suppliers %}
                            <option value="{{supplier.getId}}" {{supply.getSupplier and supply.getSupplier.getId == supplier.getId ? 'selected' : ''}}>
                                {{supplier.getName}}
                            </option>
                        {% endfor %}
                    </select>
                </div>
            </div>
```

- [ ] **Step 5: Verify**

Open `https://sop.ddev.site/admin/supplies/update?id=<any-id>`. The "Προμηθευτής" dropdown should appear, with the correct supplier pre-selected (if one was migrated). Change the supplier, save, reopen — selection should persist.

Open `https://sop.ddev.site/admin/supplies/create`. The dropdown should appear with a blank first option and all suppliers listed.

- [ ] **Step 6: Commit**

```bash
git add src/Application/Actions/Admin/CreateSupply.php \
        src/Application/Actions/Admin/UpdateSupply.php \
        src/templates/admin/create_supply.twig \
        src/templates/admin/update_supply.twig
git commit -m "feat: add supplier dropdown to supply create and update forms"
```
