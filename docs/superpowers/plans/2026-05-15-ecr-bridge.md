# ECR Bridge Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a bridge between the cloud app and a local fiscal ECR (MIRKA III / INFOCARINA i57 III) using the MCP protocol — the cloud app queues orders when placed, a local PHP CLI agent polls the cloud API and sends item sale commands to the ECR over TCP.

**Architecture:** When an order is placed (take-out or table), an `EcrJob` row is inserted into `ecr_queue`. A PHP CLI agent on the restaurant's local network polls `GET /api/ecr/jobs` every minute (via cron), sends MCP command `3` (item sale) for each order entry, and reports success/failure via `POST /api/ecr/jobs/{id}/ack`. The ECR auto-opens the transaction on the first item; the ECR operator closes it by processing payment at the physical device.

**Tech Stack:** PHP 8, Doctrine ORM 3, Slim 4, PostgreSQL, MCP protocol over TCP (fsockopen), cURL for cloud polling

**Design spec:** `docs/superpowers/specs/2026-05-15-ecr-bridge-design.md`

---

### Context for agentic workers

**Codebase conventions:**
- Entities live in `src/Domain/Entities/`, use PHP 8 ORM attributes, follow getter/setter pattern
- Repositories extend `Doctrine\ORM\EntityRepository`, have a `persist()` method calling `getEntityManager()->persist($entity); flush()`
- Actions are `final class` in `src/Application/Actions/<App>/`, have a single `__invoke(Request, Response)` method
- DI container (`app/dependencies.php`) binds repositories via `fn($c) => $c->get(EntityManager::class)->getRepository(Entity::class)`
- Routes registered in `app/routes.php` in `$app->group()` blocks
- Migrations are standalone PDO scripts in `migrations/`, idempotency-guarded, run via `ddev exec php migrations/<file>.php`
- Env vars loaded via `vlucas/phpdotenv` from `app/.env`, accessible as `$_ENV['KEY']`
- No test suite — verify with smoke tests (curl / browser / ddev exec)

**Key entities already read:**
- `MenuItem` — has `getTranslation(string $isoCode): ?MenuItemTranslation`, `getPriceUnit(): string` ('item'|'kg'), `getMenuItemPrice()` N/A — price is on `OrderEntry` as `getMenuItemPrice(): float`
- `OrderEntry` — has `getQuantity(): int`, `getMenuItemPrice(): float`, `getWeight(): ?int` (grams, set for kg items), `getOrderEntryExtras()` (collection of `OrderEntryExtra`)
- `OrderEntryExtra` — has `getName(): string`, `getPrice(): float`
- `Order` — has `getOrderEntries()`, `getId(): int`

---

### Task 1: Migration — `fiscal_department` on `menu_items`

**Files:**
- Create: `migrations/add_fiscal_department_to_menu_items.php`

- [ ] **Step 1: Create the migration file**

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

$envFile = __DIR__ . '/../app/.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found at {$envFile}\n");
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
}

$dsn = "pgsql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}";

try {
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "Connected to database '{$_ENV['DB_NAME']}'.\n";

$exists = $pdo->query("
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_name = 'menu_items' AND column_name = 'fiscal_department'
")->fetchColumn();

if ($exists) {
    die("Column 'fiscal_department' already exists on 'menu_items'. Migration may have already run.\n");
}

$pdo->beginTransaction();
try {
    $pdo->exec("ALTER TABLE menu_items ADD COLUMN fiscal_department INTEGER DEFAULT NULL");
    echo "Added column 'fiscal_department' to 'menu_items'.\n";
    $pdo->commit();
    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed: " . $e->getMessage() . "\n");
}
```

- [ ] **Step 2: Run the migration**

```bash
ddev exec php migrations/add_fiscal_department_to_menu_items.php
```

Expected output:
```
Connected to database 'db'.
Added column 'fiscal_department' to 'menu_items'.
Migration completed successfully.
```

- [ ] **Step 3: Commit**

```bash
git add migrations/add_fiscal_department_to_menu_items.php
git commit -m "feat: add fiscal_department column to menu_items"
```

---

### Task 2: Migration — `ecr_queue` table

**Files:**
- Create: `migrations/add_ecr_queue.php`

- [ ] **Step 1: Create the migration file**

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

$envFile = __DIR__ . '/../app/.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found at {$envFile}\n");
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
}

$dsn = "pgsql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}";

try {
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "Connected to database '{$_ENV['DB_NAME']}'.\n";

$exists = $pdo->query("
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_name = 'ecr_queue'
")->fetchColumn();

if ($exists) {
    die("Table 'ecr_queue' already exists. Migration may have already run.\n");
}

$pdo->beginTransaction();
try {
    $pdo->exec("
        CREATE TABLE ecr_queue (
            id               SERIAL PRIMARY KEY,
            order_id         INTEGER NOT NULL REFERENCES orders(id),
            status           VARCHAR(20) NOT NULL DEFAULT 'pending',
            attempts         INTEGER NOT NULL DEFAULT 0,
            last_attempted_at TIMESTAMPTZ DEFAULT NULL,
            error            TEXT DEFAULT NULL,
            created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
        )
    ");
    echo "Created table 'ecr_queue'.\n";
    $pdo->commit();
    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed: " . $e->getMessage() . "\n");
}
```

- [ ] **Step 2: Run the migration**

```bash
ddev exec php migrations/add_ecr_queue.php
```

Expected output:
```
Connected to database 'db'.
Created table 'ecr_queue'.
Migration completed successfully.
```

- [ ] **Step 3: Commit**

```bash
git add migrations/add_ecr_queue.php
git commit -m "feat: add ecr_queue table migration"
```

---

### Task 3: `EcrJob` entity + `EcrJobsRepository`

**Files:**
- Create: `src/Domain/Entities/EcrJob.php`
- Create: `src/Domain/Repositories/EcrJobsRepository.php`

- [ ] **Step 1: Create the `EcrJob` entity**

```php
<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTimeImmutable;
use Domain\Entities\Order;
use Domain\Repositories\EcrJobsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EcrJobsRepository::class)]
#[ORM\Table(name: 'ecr_queue')]
class EcrJob
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id')]
    private Order $order;

    #[ORM\Column(type: 'string')]
    private string $status = 'pending';

    #[ORM\Column(type: 'integer')]
    private int $attempts = 0;

    #[ORM\Column(type: 'datetimetz_immutable', name: 'last_attempted_at', nullable: true)]
    private ?DateTimeImmutable $lastAttemptedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $error = null;

    #[ORM\Column(type: 'datetimetz_immutable', name: 'created_at')]
    private DateTimeImmutable $createdAt;

    public function getId(): int { return $this->id; }
    public function getOrder(): Order { return $this->order; }
    public function getStatus(): string { return $this->status; }
    public function getAttempts(): int { return $this->attempts; }
    public function getLastAttemptedAt(): ?DateTimeImmutable { return $this->lastAttemptedAt; }
    public function getError(): ?string { return $this->error; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }

    public function setOrder(Order $order): void { $this->order = $order; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setAttempts(int $attempts): void { $this->attempts = $attempts; }
    public function setLastAttemptedAt(?DateTimeImmutable $lastAttemptedAt): void { $this->lastAttemptedAt = $lastAttemptedAt; }
    public function setError(?string $error): void { $this->error = $error; }
    public function setCreatedAt(DateTimeImmutable $createdAt): void { $this->createdAt = $createdAt; }
}
```

- [ ] **Step 2: Create `EcrJobsRepository`**

```php
<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\EcrJob;
use Doctrine\ORM\EntityRepository;

class EcrJobsRepository extends EntityRepository
{
    public function persist(EcrJob $job): void
    {
        $this->getEntityManager()->persist($job);
        $this->getEntityManager()->flush();
    }

    /** @return EcrJob[] */
    public function findPending(): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.status = :status')
            ->andWhere('j.attempts < :maxAttempts')
            ->setParameter('status', 'pending')
            ->setParameter('maxAttempts', 5)
            ->getQuery()
            ->getResult();
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Domain/Entities/EcrJob.php src/Domain/Repositories/EcrJobsRepository.php
git commit -m "feat: add EcrJob entity and EcrJobsRepository"
```

---

### Task 4: `fiscal_department` on `MenuItem` + admin UI

**Files:**
- Modify: `src/Domain/Entities/MenuItem.php`
- Modify: `src/Application/Actions/Admin/UpdateMenuItem.php`
- Modify: `src/Application/Actions/Admin/CopyMenuItem.php`
- Modify: `src/templates/admin/update_menu_item.twig`

- [ ] **Step 1: Add `fiscal_department` to `MenuItem`**

In `src/Domain/Entities/MenuItem.php`, add the property after the `$barcode` declaration (around line 45):

```php
#[ORM\Column(type: 'integer', name: 'fiscal_department', nullable: true)]
private ?int $fiscalDepartment = null;
```

Add the getter after `getBarcode()` (around line 113):

```php
public function getFiscalDepartment(): ?int
{
    return $this->fiscalDepartment;
}
```

Add the setter after `setBarcode()` (around line 222):

```php
public function setFiscalDepartment(?int $fiscalDepartment): void
{
    $this->fiscalDepartment = $fiscalDepartment;
}
```

- [ ] **Step 2: Persist `fiscal_department` in `UpdateMenuItem`**

In `src/Application/Actions/Admin/UpdateMenuItem.php`, add after `$menuItem->setBarcode(...)` (line 62):

```php
$menuItem->setFiscalDepartment(
    isset($requestData['fiscalDepartment']) && $requestData['fiscalDepartment'] !== ''
        ? intval($requestData['fiscalDepartment'])
        : null
);
```

- [ ] **Step 3: Copy `fiscal_department` in `CopyMenuItem`**

In `src/Application/Actions/Admin/CopyMenuItem.php`, add after `$newItem->setBarcode(...)` (around line 55):

```php
$newItem->setFiscalDepartment($sourceItem->getFiscalDepartment());
```

- [ ] **Step 4: Add input to `update_menu_item.twig`**

In `src/templates/admin/update_menu_item.twig`, add after the Barcode row (after the closing `</div>` of the barcode row, around line 113):

```twig
<div class="row mb-3">
    <label class="col-sm-2 col-form-label">Τμήμα ΦΤΜ</label>
    <div class="col-sm-10">
        <input
            type="number"
            min="1"
            step="1"
            class="form-control"
            name="fiscalDepartment"
            value="{{menuItem.getFiscalDepartment}}"
        />
    </div>
</div>
```

- [ ] **Step 5: Smoke test the form**

1. Navigate to `https://sop.ddev.site/admin/menu-items/update?id=<any-id>`
2. Confirm the "Τμήμα ΦΤΜ" field appears below the Barcode field
3. Enter `1` and save — reload and confirm the value is preserved
4. Clear the field and save — confirm it saves as empty (NULL)

- [ ] **Step 6: Commit**

```bash
git add src/Domain/Entities/MenuItem.php \
        src/Application/Actions/Admin/UpdateMenuItem.php \
        src/Application/Actions/Admin/CopyMenuItem.php \
        src/templates/admin/update_menu_item.twig
git commit -m "feat: add fiscal_department field to MenuItem and admin UI"
```

---

### Task 5: DI binding + `.env` + routes

**Files:**
- Modify: `app/dependencies.php`
- Modify: `app/.env.example`
- Modify: `app/routes.php`

- [ ] **Step 1: Add `EcrJobsRepository` to DI container**

In `app/dependencies.php`, add to the `use` imports at the top:

```php
use Domain\Entities\EcrJob;
use Domain\Repositories\EcrJobsRepository;
```

In the `$containerBuilder->addDefinitions([...])` array, add (following the existing repository pattern):

```php
EcrJobsRepository::class => function (ContainerInterface $c) {
    $em = $c->get(EntityManager::class);
    return $em->getRepository(EcrJob::class);
},
```

- [ ] **Step 2: Add `ECR_AGENT_API_KEY` to `.env.example`**

In `app/.env.example`, add:

```
ECR_AGENT_API_KEY=""
```

Also add the key to your local `app/.env`:

```
ECR_AGENT_API_KEY="<generate a random secret, e.g. openssl rand -hex 32>"
```

- [ ] **Step 3: Register API routes in `routes.php`**

At the top of `app/routes.php`, add to the `use` imports:

```php
use Application\Actions\Api\EcrJobs;
use Application\Actions\Api\AckEcrJob;
```

At the end of `app/routes.php`, before the closing of the file, add a new route group (no auth middleware — this uses the `X-Api-Key` header instead):

```php
$app->group('/api', function (RouteCollectorProxy $group) {
    $group->get('/ecr/jobs', EcrJobs::class);
    $group->post('/ecr/jobs/{id}/ack', AckEcrJob::class);
});
```

- [ ] **Step 4: Commit**

```bash
git add app/dependencies.php app/.env.example app/routes.php
git commit -m "feat: register EcrJobsRepository in DI, add ECR API routes and env key"
```

---

### Task 6: Cloud API actions — `EcrJobs` (GET) and `AckEcrJob` (POST)

**Files:**
- Create: `src/Application/Actions/Api/EcrJobs.php`
- Create: `src/Application/Actions/Api/AckEcrJob.php`

- [ ] **Step 1: Create the `EcrJobs` action**

Create directory `src/Application/Actions/Api/` if it doesn't exist, then create:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Api;

use Domain\Repositories\EcrJobsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class EcrJobs
{
    public function __construct(private EcrJobsRepository $ecrJobsRepository) {}

    public function __invoke(Request $request, Response $response): Response
    {
        if ($request->getHeaderLine('X-Api-Key') !== ($_ENV['ECR_AGENT_API_KEY'] ?? '')) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $jobs = $this->ecrJobsRepository->findPending();

        $result = [];
        foreach ($jobs as $job) {
            $entries = [];

            foreach ($job->getOrder()->getOrderEntries() as $orderEntry) {
                $menuItem = $orderEntry->getMenuItem();
                $name = mb_substr(
                    $menuItem->getTranslation('el')?->getName()
                    ?? $menuItem->getTranslation('en')?->getName()
                    ?? '',
                    0,
                    20
                );

                $isKg = $menuItem->getPriceUnit() === 'kg';
                $qty = $isKg
                    ? number_format(($orderEntry->getWeight() ?? 1000) / 1000.0, 3, '.', '')
                    : number_format($orderEntry->getQuantity(), 3, '.', '');

                $entries[] = [
                    'name'             => $name,
                    'quantity'         => $qty,
                    'unitPrice'        => number_format($orderEntry->getMenuItemPrice(), 2, '.', ''),
                    'fiscalDepartment' => $menuItem->getFiscalDepartment(),
                ];

                foreach ($orderEntry->getOrderEntryExtras() as $extra) {
                    $entries[] = [
                        'name'             => mb_substr($extra->getName(), 0, 20),
                        'quantity'         => '1.000',
                        'unitPrice'        => number_format($extra->getPrice(), 2, '.', ''),
                        'fiscalDepartment' => $menuItem->getFiscalDepartment(),
                    ];
                }
            }

            $result[] = [
                'id'      => $job->getId(),
                'orderId' => $job->getOrder()->getId(),
                'entries' => $entries,
            ];
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

- [ ] **Step 2: Create the `AckEcrJob` action**

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Api;

use DateTimeImmutable;
use Domain\Repositories\EcrJobsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AckEcrJob
{
    public function __construct(private EcrJobsRepository $ecrJobsRepository) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        if ($request->getHeaderLine('X-Api-Key') !== ($_ENV['ECR_AGENT_API_KEY'] ?? '')) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $job = $this->ecrJobsRepository->find((int) $args['id']);
        if ($job === null) {
            $response->getBody()->write(json_encode(['error' => 'Not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $body = json_decode(file_get_contents('php://input'), true);

        $newAttempts = $job->getAttempts() + 1;
        $job->setAttempts($newAttempts);
        $job->setLastAttemptedAt(new DateTimeImmutable());

        if ($newAttempts >= 5) {
            $job->setStatus('failed');
            $job->setError($body['error'] ?? $job->getError());
        } elseif (($body['status'] ?? '') === 'sent') {
            $job->setStatus('sent');
        } else {
            $job->setStatus('pending');
            $job->setError($body['error'] ?? null);
        }

        $this->ecrJobsRepository->persist($job);

        $response->getBody()->write('ok');
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

- [ ] **Step 3: Smoke test both endpoints**

Test the GET endpoint with no key (expect 401):
```bash
curl -s https://sop.ddev.site/api/ecr/jobs
```
Expected: `{"error":"Unauthorized"}`

Test with the correct key (expect empty array if no orders placed yet):
```bash
curl -s -H "X-Api-Key: <your-ECR_AGENT_API_KEY>" https://sop.ddev.site/api/ecr/jobs
```
Expected: `[]`

Test the POST ack endpoint with a fake id (expect 404):
```bash
curl -s -X POST -H "X-Api-Key: <your-key>" -H "Content-Type: application/json" \
  -d '{"status":"sent"}' https://sop.ddev.site/api/ecr/jobs/99999/ack
```
Expected: `{"error":"Not found"}`

- [ ] **Step 4: Commit**

```bash
git add src/Application/Actions/Api/
git commit -m "feat: add EcrJobs and AckEcrJob API actions"
```

---

### Task 7: Enqueue ECR jobs in `CreateOrder` (take-out + orders-app)

**Files:**
- Modify: `src/Application/Actions/TakeOutApp/CreateOrder.php`
- Modify: `src/Application/Actions/OrdersApp/CreateOrder.php`

- [ ] **Step 1: Update `TakeOutApp/CreateOrder`**

In `src/Application/Actions/TakeOutApp/CreateOrder.php`:

Add imports at the top:
```php
use Domain\Entities\EcrJob;
use Domain\Repositories\EcrJobsRepository;
```

Add `EcrJobsRepository` to the constructor:
```php
public function __construct(
    private Twig $twig,
    private MenuItemsRepository $menuItemsRepository,
    private OrdersRepository $ordersRepository,
    private UsersRepository $usersRepository,
    private EcrJobsRepository $ecrJobsRepository
) {}
```

After `$this->ordersRepository->persist($order);` (line 105), add:
```php
$ecrJob = new EcrJob();
$ecrJob->setOrder($order);
$ecrJob->setStatus('pending');
$ecrJob->setAttempts(0);
$ecrJob->setCreatedAt($now);
$this->ecrJobsRepository->persist($ecrJob);
```

- [ ] **Step 2: Update `OrdersApp/CreateOrder`**

In `src/Application/Actions/OrdersApp/CreateOrder.php`:

Add imports at the top:
```php
use Domain\Entities\EcrJob;
use Domain\Repositories\EcrJobsRepository;
```

Add `EcrJobsRepository` to the constructor:
```php
public function __construct(
    private Twig $twig,
    private MenuItemsRepository $menuItemsRepository,
    private OrdersRepository $ordersRepository,
    private ReservationsRepository $reservationsRepository,
    private TablesRepository $tablesRepository,
    private UsersRepository $usersRepository,
    private EcrJobsRepository $ecrJobsRepository
) {}
```

After `$this->ordersRepository->persist($order);` (line 111), add:
```php
$ecrJob = new EcrJob();
$ecrJob->setOrder($order);
$ecrJob->setStatus('pending');
$ecrJob->setAttempts(0);
$ecrJob->setCreatedAt(new DateTimeImmutable());
$this->ecrJobsRepository->persist($ecrJob);
```

- [ ] **Step 3: Smoke test**

1. Place a take-out order at `https://sop.ddev.site/take-out/create`
2. Call the polling endpoint:
```bash
curl -s -H "X-Api-Key: <your-key>" https://sop.ddev.site/api/ecr/jobs
```
Expected: a JSON array containing one job with `entries` matching the order items, quantities, prices, and `fiscalDepartment` values (null if none set yet on those items).

3. Set a fiscal department on one of the menu items in the admin, place a new order, and verify `fiscalDepartment` is populated in the response.

- [ ] **Step 4: Commit**

```bash
git add src/Application/Actions/TakeOutApp/CreateOrder.php \
        src/Application/Actions/OrdersApp/CreateOrder.php
git commit -m "feat: enqueue ECR job when order is placed"
```

---

### Task 8: Local agent

**Files:**
- Create: `ecr-agent/ecr-agent-config.example.php`
- Create: `ecr-agent/ecr-agent.php`
- Modify: `.gitignore`

- [ ] **Step 1: Create the config example**

```php
<?php

return [
    'ecr_host'      => '192.168.1.100',    // IP address of the ECR on the local network
    'ecr_port'      => 9100,               // TCP port (confirm with device vendor)
    'cloud_api_url' => 'https://your-app.example.com',
    'api_key'       => 'your-api-key-here',
    'timeout'       => 5,                  // socket timeout in seconds
    'max_retries'   => 3,                  // ENQ and packet retransmit limit
];
```

- [ ] **Step 2: Add `ecr-agent-config.php` to `.gitignore`**

Add this line to `.gitignore`:

```
ecr-agent/ecr-agent-config.php
```

- [ ] **Step 3: Create the main agent script**

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

$config = require __DIR__ . '/ecr-agent-config.php';

// Prevent concurrent cron runs
$lockFile = sys_get_temp_dir() . '/ecr-agent.lock';
$lock = fopen($lockFile, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo date('Y-m-d H:i:s') . " Already running, exiting.\n";
    exit(0);
}

function log_msg(string $msg): void
{
    echo date('Y-m-d H:i:s') . ' ' . $msg . "\n";
}

function http_get(string $url, string $apiKey): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['X-Api-Key: ' . $apiKey],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    if ($body === false) {
        return null;
    }
    return json_decode($body, true);
}

function http_post(string $url, string $apiKey, array $data): void
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [
            'X-Api-Key: ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function calc_checksum(string $data): string
{
    $sum = 0;
    foreach (str_split($data) as $char) {
        $sum += ord($char);
    }
    return sprintf('%02d', $sum % 100);
}

function read_byte($socket, int $timeout): string
{
    $read = [$socket];
    $write = $except = null;
    if (stream_select($read, $write, $except, $timeout) < 1) {
        throw new RuntimeException("Socket read timeout");
    }
    $byte = fread($socket, 1);
    if ($byte === false || $byte === '') {
        throw new RuntimeException("Socket closed unexpectedly");
    }
    return $byte;
}

function read_packet($socket, int $timeout): string
{
    // Wait for STX (0x02), discard anything before it
    $deadline = time() + $timeout;
    do {
        if (time() > $deadline) {
            throw new RuntimeException("Timeout waiting for STX");
        }
        $byte = read_byte($socket, $timeout);
    } while ($byte !== chr(0x02));

    // Read until ETX (0x03), accumulate only printable bytes (>=32)
    $data    = '';
    $deadline = time() + $timeout;
    while (true) {
        if (time() > $deadline) {
            throw new RuntimeException("Timeout waiting for ETX");
        }
        $byte = read_byte($socket, $timeout);
        if ($byte === chr(0x03)) {
            break;
        }
        if (ord($byte) >= 32) {
            $data .= $byte;
        }
    }
    return $data;
}

function send_item_sale($socket, array $entry, int $maxRetries, int $timeout): void
{
    // Build data string: 3/S/<name>//<qty>/<unitPrice>/<dept>/
    // Field 4 (extra description) is intentionally empty
    $name = mb_substr($entry['name'], 0, 20);
    $data = sprintf(
        '3/S/%s//%s/%s/%d/',
        $name,
        $entry['quantity'],
        $entry['unitPrice'],
        $entry['fiscalDepartment']
    );
    $data .= calc_checksum($data); // 2-digit checksum appended

    // Step 1: ENQ handshake — send ENQ (0x05), wait for ACK (0x06)
    $acked = false;
    for ($i = 0; $i < $maxRetries; $i++) {
        fwrite($socket, chr(0x05));
        $resp = read_byte($socket, $timeout);
        if ($resp === chr(0x06)) {
            $acked = true;
            break;
        }
    }
    if (!$acked) {
        throw new RuntimeException("ENQ not acknowledged after {$maxRetries} attempts");
    }

    // Step 2: Send packet — STX + data + ETX, wait for ACK
    $packet = chr(0x02) . $data . chr(0x03);
    $acked  = false;
    for ($i = 0; $i < $maxRetries; $i++) {
        fwrite($socket, $packet);
        $resp = read_byte($socket, $timeout);
        if ($resp === chr(0x06)) {
            $acked = true;
            break;
        }
    }
    if (!$acked) {
        throw new RuntimeException("Packet not acknowledged after {$maxRetries} attempts");
    }

    // Step 3: Receive reply packet, send ACK
    $reply = read_packet($socket, $timeout);
    fwrite($socket, chr(0x06)); // ACK the reply

    // Verify reply code matches request code '3'
    $parts = explode('/', $reply);
    if (($parts[0] ?? '') !== '3') {
        throw new RuntimeException(
            "Unexpected reply code: " . ($parts[0] ?? 'none') . " (full reply: {$reply})"
        );
    }
}

function ack_job(int $jobId, string $status, ?string $error, array $config): void
{
    $url  = rtrim($config['cloud_api_url'], '/') . '/api/ecr/jobs/' . $jobId . '/ack';
    $data = ['status' => $status];
    if ($error !== null) {
        $data['error'] = $error;
    }
    http_post($url, $config['api_key'], $data);
}

// ── Main ──────────────────────────────────────────────────────────────────────

$url  = rtrim($config['cloud_api_url'], '/') . '/api/ecr/jobs';
$jobs = http_get($url, $config['api_key']);

if (!is_array($jobs)) {
    log_msg("ERROR: Failed to fetch jobs from cloud API.");
    flock($lock, LOCK_UN);
    fclose($lock);
    exit(1);
}

if (empty($jobs)) {
    log_msg("No pending jobs.");
    flock($lock, LOCK_UN);
    fclose($lock);
    exit(0);
}

log_msg("Processing " . count($jobs) . " job(s).");

foreach ($jobs as $job) {
    $jobId  = (int) $job['id'];
    $socket = null;

    try {
        // Validate fiscal departments before touching the ECR
        foreach ($job['entries'] as $entry) {
            if ($entry['fiscalDepartment'] === null) {
                throw new RuntimeException(
                    "Entry '{$entry['name']}' has no fiscal department configured."
                );
            }
        }

        // Open TCP socket to ECR
        $socket = @fsockopen(
            $config['ecr_host'],
            $config['ecr_port'],
            $errno,
            $errstr,
            $config['timeout']
        );
        if (!$socket) {
            throw new RuntimeException(
                "Cannot connect to ECR at {$config['ecr_host']}:{$config['ecr_port']}: {$errstr} ({$errno})"
            );
        }
        stream_set_timeout($socket, $config['timeout']);

        // Send one item sale command per entry
        foreach ($job['entries'] as $entry) {
            send_item_sale($socket, $entry, $config['max_retries'], $config['timeout']);
        }

        fclose($socket);

        ack_job($jobId, 'sent', null, $config);
        log_msg("Job {$jobId} (order {$job['orderId']}): sent successfully.");

    } catch (RuntimeException $e) {
        if ($socket) {
            fclose($socket);
        }
        ack_job($jobId, 'failed', $e->getMessage(), $config);
        log_msg("Job {$jobId} (order {$job['orderId']}): FAILED — " . $e->getMessage());
    }
}

flock($lock, LOCK_UN);
fclose($lock);
```

- [ ] **Step 4: Commit**

```bash
git add ecr-agent/ .gitignore
git commit -m "feat: add ECR local agent script"
```

---

### Task 9: Local server setup & smoke test

This task is performed on the **local restaurant server**, not in DDEV.

- [ ] **Step 1: Copy the agent to the local server**

Copy the `ecr-agent/` directory to the local server. Then create the config:

```bash
cp ecr-agent/ecr-agent-config.example.php ecr-agent/ecr-agent-config.php
```

Edit `ecr-agent-config.php` with the real values:
- `ecr_host`: the ECR's IP address on the local network
- `ecr_port`: the TCP port the ECR's network adapter listens on (check device/adapter documentation — commonly 9100 or 4000)
- `cloud_api_url`: the production URL of the cloud app
- `api_key`: the same value as `ECR_AGENT_API_KEY` in the cloud app's `.env`

- [ ] **Step 2: Verify PHP and cURL are available**

```bash
php -v
php -r "echo function_exists('curl_init') ? 'cURL OK' : 'cURL missing';"
```

Both should succeed. If cURL is missing: `sudo apt-get install php-curl` (or equivalent).

- [ ] **Step 3: Run the agent manually with no pending jobs**

```bash
php ecr-agent/ecr-agent.php
```

Expected output:
```
2026-05-15 12:00:00 No pending jobs.
```

- [ ] **Step 4: Place a test order and run the agent**

1. Set `fiscal_department = 1` on a simple menu item in the admin
2. Place an order containing that item (take-out or table)
3. Verify the job appears in the cloud API:
```bash
curl -s -H "X-Api-Key: <key>" https://your-app.example.com/api/ecr/jobs
```
4. Run the agent:
```bash
php ecr-agent/ecr-agent.php
```

If the ECR is reachable, expected output:
```
2026-05-15 12:01:00 Processing 1 job(s).
2026-05-15 12:01:00 Job 1 (order 42): sent successfully.
```

The ECR should show the item on its display and have an open transaction ready for payment.

If the ECR is unreachable (connection refused), expected output:
```
2026-05-15 12:01:00 Processing 1 job(s).
2026-05-15 12:01:00 Job 1 (order 42): FAILED — Cannot connect to ECR at 192.168.1.100:9100: ...
```

The job will be retried on the next cron run (up to 5 attempts total).

- [ ] **Step 5: Add the cron job**

```bash
crontab -e
```

Add:
```
* * * * * php /path/to/ecr-agent/ecr-agent.php >> /var/log/ecr-agent.log 2>&1
```

Verify it runs:
```bash
tail -f /var/log/ecr-agent.log
```

- [ ] **Step 6: Final commit (no code changes — just documenting setup)**

No commit needed for this task. Setup is environment-specific.
