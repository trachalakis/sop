# Customer-Facing Take-Out Mini Site Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Public `/order` mini site where customers browse the Take Out menu, submit a pickup order into a pending queue, and track its status; staff accept (with ETA) or reject from the existing take-out app.

**Architecture:** New `TakeOutRequest` entity family (request + entries + extras) fully separate from `Order`. Order-creation logic is extracted from the staff `CreateOrder` action into a shared `TakeOutOrderFactory`; accepting a request runs it to produce a real `Order` (ticket number, ECR job, quantity decrement). Customer pages are server-rendered Twig with Vue (CDN) for the cart, mobile-first, with an el/en toggle.

**Tech Stack:** Slim 4, Doctrine ORM 3 (PHP 8 attributes), Twig 3, PostgreSQL, Vue 3 + axios (existing local assets), Bootstrap 5.3 (CDN). No test suite exists in this repo — each task ends with lint + manual/curl verification instead of automated tests.

**Spec:** `docs/superpowers/specs/2026-06-12-customer-take-out-site-design.md`

**Deviations from spec (verified against the codebase/DB):**
1. gettext is referenced by the `|_` Twig filter but never configured (no `.po` files, no `setlocale`) — the el/en toggle is implemented with a small JS dictionary plus the existing `MenuItemTranslation`/`MenuSectionTranslation` data (languages `el`/`en` via `Language::getIsoCode()`).
2. The existing `/take-out` staff routes have **no** `user_permissions` rows (access works via the `webmaster` bypass). The new staff endpoints under `/take-out` inherit exactly that behavior — no permissions migration.
3. Items priced by weight (`priceUnit == 'kg'`) are excluded from the customer menu: the customer total must be exact, and weight is only known at the counter.

**Conventions to follow:** `declare(strict_types=1)`, one action class per route with `__invoke(Request, Response)`, repositories bound in `app/dependencies.php`, entities mirror `Order`/`OrderEntry`/`OrderEntryExtra` style. All commands run via `ddev`.

---

### Task 1: Database migration

**Files:**
- Create: `migrations/create_take_out_requests.php`

- [ ] **Step 1: Write the migration**

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

$host     = $_ENV['DB_HOST']     ?? '';
$username = $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';
$dbName   = $_ENV['DB_NAME']     ?? '';

$dsn = "pgsql:host={$host};dbname={$dbName}";

try {
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "Connected to database '{$dbName}'.\n";

$tableExists = $pdo->query("
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_name = 'take_out_requests'
")->fetchColumn();

if ($tableExists) {
    die("Table 'take_out_requests' already exists. Migration may have already run.\n");
}

$pdo->beginTransaction();

try {
    $pdo->exec("
        CREATE TABLE take_out_requests (
            id              SERIAL PRIMARY KEY,
            created_at      TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            token           VARCHAR(36) NOT NULL UNIQUE,
            customer_name   VARCHAR(100) NOT NULL,
            customer_phone  VARCHAR(20) NOT NULL,
            notes           TEXT NOT NULL DEFAULT '',
            status          VARCHAR(16) NOT NULL,
            eta_minutes     INTEGER,
            responded_at    TIMESTAMP(0) WITHOUT TIME ZONE,
            order_id        INTEGER REFERENCES orders(id) ON DELETE SET NULL
        )
    ");
    echo "Created table 'take_out_requests'.\n";

    $pdo->exec("CREATE INDEX idx_take_out_requests_status ON take_out_requests (status)");
    echo "Created index on (status).\n";

    $pdo->exec("
        CREATE TABLE take_out_request_entries (
            id              SERIAL PRIMARY KEY,
            request_id      INTEGER NOT NULL REFERENCES take_out_requests(id) ON DELETE CASCADE,
            menu_item_id    INTEGER NOT NULL REFERENCES menu_items(id),
            menu_item_price DOUBLE PRECISION NOT NULL,
            quantity        INTEGER NOT NULL
        )
    ");
    echo "Created table 'take_out_request_entries'.\n";

    $pdo->exec("
        CREATE TABLE take_out_request_entry_extras (
            id              SERIAL PRIMARY KEY,
            entry_id        INTEGER NOT NULL REFERENCES take_out_request_entries(id) ON DELETE CASCADE,
            name            VARCHAR(255) NOT NULL,
            price           DOUBLE PRECISION NOT NULL
        )
    ");
    echo "Created table 'take_out_request_entry_extras'.\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
```

- [ ] **Step 2: Run the migration**

Run: `ddev exec php migrations/create_take_out_requests.php`
Expected output ends with: `Migration completed successfully.`

- [ ] **Step 3: Verify tables exist**

Run: `ddev exec 'psql -U db -d db -h db -c "\d take_out_requests"'`
Expected: table definition with columns `id, created_at, token, customer_name, customer_phone, notes, status, eta_minutes, responded_at, order_id`.

- [ ] **Step 4: Commit**

```bash
git add migrations/create_take_out_requests.php
git commit -m "Add migration for take-out request tables"
```

---

### Task 2: Enum, entities, repository, DI binding

**Files:**
- Create: `src/Domain/Enums/TakeOutRequestStatus.php`
- Create: `src/Domain/Entities/TakeOutRequest.php`
- Create: `src/Domain/Entities/TakeOutRequestEntry.php`
- Create: `src/Domain/Entities/TakeOutRequestEntryExtra.php`
- Create: `src/Domain/Repositories/TakeOutRequestsRepository.php`
- Modify: `app/dependencies.php`

- [ ] **Step 1: Create the status enum**

`src/Domain/Enums/TakeOutRequestStatus.php`:

```php
<?php

declare(strict_types=1);

namespace Domain\Enums;

enum TakeOutRequestStatus: string
{
    case Pending = 'PENDING';
    case Accepted = 'ACCEPTED';
    case Rejected = 'REJECTED';
}
```

- [ ] **Step 2: Create `TakeOutRequest` entity**

`src/Domain/Entities/TakeOutRequest.php`:

```php
<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTimeImmutable;
use Domain\Enums\TakeOutRequestStatus;
use Domain\Repositories\TakeOutRequestsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TakeOutRequestsRepository::class)]
#[ORM\Table(name: 'take_out_requests')]
class TakeOutRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', name: 'token', unique: true)]
    private string $token;

    #[ORM\Column(type: 'string', name: 'customer_name')]
    private string $customerName;

    #[ORM\Column(type: 'string', name: 'customer_phone')]
    private string $customerPhone;

    #[ORM\Column(type: 'text', name: 'notes')]
    private string $notes;

    #[ORM\Column(type: 'string', enumType: TakeOutRequestStatus::class, name: 'status')]
    private TakeOutRequestStatus $status;

    #[ORM\Column(type: 'integer', name: 'eta_minutes', nullable: true)]
    private ?int $etaMinutes;

    #[ORM\Column(type: 'datetime_immutable', name: 'responded_at', nullable: true)]
    private ?DateTimeImmutable $respondedAt;

    #[ORM\OneToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: true)]
    private ?Order $order;

    #[ORM\OneToMany(targetEntity: TakeOutRequestEntry::class, mappedBy: 'request', cascade: ['persist'], orphanRemoval: true)]
    private $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCustomerPhone(): string
    {
        return $this->customerPhone;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getStatus(): TakeOutRequestStatus
    {
        return $this->status;
    }

    public function getEtaMinutes(): ?int
    {
        return $this->etaMinutes;
    }

    public function getRespondedAt(): ?DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getEntries()
    {
        return $this->entries;
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->entries as $entry) {
            $total += $entry->getPrice();
        }

        return round($total, 1);
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function setCustomerName(string $customerName): void
    {
        $this->customerName = $customerName;
    }

    public function setCustomerPhone(string $customerPhone): void
    {
        $this->customerPhone = $customerPhone;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function setStatus(TakeOutRequestStatus $status): void
    {
        $this->status = $status;
    }

    public function setEtaMinutes(?int $etaMinutes): void
    {
        $this->etaMinutes = $etaMinutes;
    }

    public function setRespondedAt(?DateTimeImmutable $respondedAt): void
    {
        $this->respondedAt = $respondedAt;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function setEntries($entries): void
    {
        $this->entries = $entries;
    }
}
```

- [ ] **Step 3: Create `TakeOutRequestEntry` entity**

`src/Domain/Entities/TakeOutRequestEntry.php`:

```php
<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'take_out_request_entries')]
class TakeOutRequestEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: TakeOutRequest::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id')]
    private TakeOutRequest $request;

    #[ORM\ManyToOne(targetEntity: MenuItem::class)]
    #[ORM\JoinColumn(name: 'menu_item_id', referencedColumnName: 'id')]
    private MenuItem $menuItem;

    #[ORM\Column(type: 'float', name: 'menu_item_price')]
    private float $menuItemPrice;

    #[ORM\Column(type: 'integer', name: 'quantity')]
    private int $quantity;

    #[ORM\OneToMany(targetEntity: TakeOutRequestEntryExtra::class, mappedBy: 'entry', cascade: ['persist'], orphanRemoval: true)]
    private $extras;

    public function __construct()
    {
        $this->extras = new ArrayCollection;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRequest(): TakeOutRequest
    {
        return $this->request;
    }

    public function getMenuItem(): MenuItem
    {
        return $this->menuItem;
    }

    public function getMenuItemPrice(): float
    {
        return $this->menuItemPrice;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getExtras()
    {
        return $this->extras;
    }

    public function getPrice(): float
    {
        $price = $this->menuItemPrice;

        foreach ($this->extras as $extra) {
            $price += $extra->getPrice();
        }

        return round($this->quantity * $price, 1);
    }

    public function setRequest(TakeOutRequest $request): void
    {
        $this->request = $request;
    }

    public function setMenuItem(MenuItem $menuItem): void
    {
        $this->menuItem = $menuItem;
    }

    public function setMenuItemPrice(float $menuItemPrice): void
    {
        $this->menuItemPrice = $menuItemPrice;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function setExtras($extras): void
    {
        $this->extras = $extras;
    }
}
```

- [ ] **Step 4: Create `TakeOutRequestEntryExtra` entity**

`src/Domain/Entities/TakeOutRequestEntryExtra.php`:

```php
<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'take_out_request_entry_extras')]
class TakeOutRequestEntryExtra
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: TakeOutRequestEntry::class, inversedBy: 'extras')]
    #[ORM\JoinColumn(name: 'entry_id', referencedColumnName: 'id')]
    private TakeOutRequestEntry $entry;

    #[ORM\Column(type: 'string', name: 'name')]
    private string $name;

    #[ORM\Column(type: 'float', name: 'price')]
    private float $price;

    public function getId(): int
    {
        return $this->id;
    }

    public function getEntry(): TakeOutRequestEntry
    {
        return $this->entry;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setEntry(TakeOutRequestEntry $entry): void
    {
        $this->entry = $entry;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }
}
```

- [ ] **Step 5: Create the repository**

`src/Domain/Repositories/TakeOutRequestsRepository.php`:

```php
<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\TakeOutRequest;
use Domain\Enums\TakeOutRequestStatus;
use Doctrine\ORM\EntityRepository;

class TakeOutRequestsRepository extends EntityRepository
{
    public function findPending(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('r')
            ->from('Domain\Entities\TakeOutRequest', 'r')
            ->where('r.status = :status')
            ->setParameter('status', TakeOutRequestStatus::Pending)
            ->orderBy('r.createdAt', 'asc')
            ->getQuery()
            ->getResult();
    }

    public function findOneByToken(string $token): ?TakeOutRequest
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function countPendingByPhone(string $phone): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from('Domain\Entities\TakeOutRequest', 'r')
            ->where('r.status = :status')
            ->andWhere('r.customerPhone = :phone')
            ->setParameter('status', TakeOutRequestStatus::Pending)
            ->setParameter('phone', $phone)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function persist(TakeOutRequest $request)
    {
        $this->getEntityManager()->persist($request);
        $this->getEntityManager()->flush();
    }
}
```

- [ ] **Step 6: Bind the repository in the DI container**

In `app/dependencies.php`, add to the imports block (after `use Domain\Entities\EcrJob;`):

```php
use Domain\Entities\TakeOutRequest;
use Domain\Repositories\TakeOutRequestsRepository;
```

And add a definition right after the `EcrJobsRepository::class` entry:

```php
TakeOutRequestsRepository::class => function (ContainerInterface $c) {
    $em = $c->get(EntityManager::class);

    return $em->getRepository(TakeOutRequest::class);
},
```

- [ ] **Step 7: Lint and verify Doctrine can hydrate the entities**

Run:
```bash
ddev exec php -l src/Domain/Enums/TakeOutRequestStatus.php
ddev exec php -l src/Domain/Entities/TakeOutRequest.php
ddev exec php -l src/Domain/Entities/TakeOutRequestEntry.php
ddev exec php -l src/Domain/Entities/TakeOutRequestEntryExtra.php
ddev exec php -l src/Domain/Repositories/TakeOutRequestsRepository.php
ddev exec php -l app/dependencies.php
```
Expected: `No syntax errors detected` for each.

Then smoke-test entity metadata against the live schema by inserting and reading a row:

```bash
ddev exec 'psql -U db -d db -h db -c "INSERT INTO take_out_requests (created_at, token, customer_name, customer_phone, notes, status) VALUES (NOW(), '\''smoke-test-token'\'', '\''Test'\'', '\''2101234567'\'', '\'''\'', '\''PENDING'\'')"'
curl -k -s https://sop.ddev.site/login -o /dev/null -w "%{http_code}\n"
ddev exec 'psql -U db -d db -h db -c "DELETE FROM take_out_requests WHERE token = '\''smoke-test-token'\''"'
```
Expected: INSERT 0 1, then 200, then DELETE 1. (Full ORM round-trip is exercised in Task 5's verification.)

- [ ] **Step 8: Commit**

```bash
git add src/Domain/Enums/TakeOutRequestStatus.php src/Domain/Entities/TakeOutRequest.php src/Domain/Entities/TakeOutRequestEntry.php src/Domain/Entities/TakeOutRequestEntryExtra.php src/Domain/Repositories/TakeOutRequestsRepository.php app/dependencies.php
git commit -m "Add TakeOutRequest entities, repository, and DI binding"
```

---

### Task 3: Extract `TakeOutOrderFactory` from the staff CreateOrder action

**Files:**
- Create: `src/Application/Services/TakeOutOrderFactory.php`
- Modify: `src/Application/Actions/TakeOutApp/CreateOrder.php`
- Modify: `app/dependencies.php`

The factory body is a faithful extraction of `CreateOrder.php:37-115` — same setters, same persistence order (menu items, then order, then ECR job). The only generalization: `menuItemPrice` comes from the entry spec so the accept flow can pass the price snapshotted at submission.

- [ ] **Step 1: Create the factory service**

`src/Application/Services/TakeOutOrderFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Services;

use DateTimeImmutable;
use Domain\Entities\EcrJob;
use Domain\Entities\Order;
use Domain\Entities\OrderEntry;
use Domain\Entities\OrderEntryExtra;
use Domain\Entities\OrderEntryGroup;
use Domain\Entities\User;
use Domain\Repositories\EcrJobsRepository;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\OrdersRepository;
use Ramsey\Uuid\Uuid;

final class TakeOutOrderFactory
{
    public function __construct(
        private EcrJobsRepository $ecrJobsRepository,
        private MenuItemsRepository $menuItemsRepository,
        private OrdersRepository $ordersRepository
    ) {}

    /**
     * @param list<array{
     *     menuItem: \Domain\Entities\MenuItem,
     *     menuItemPrice: float,
     *     quantity: int,
     *     timing: int,
     *     notes: string,
     *     weight: ?int,
     *     extras: list<array{name: string, price: float}>
     * }> $entrySpecs
     */
    public function create(array $entrySpecs, string $groupNotes, User $waiter, bool $markAsPaid): Order
    {
        $now = new DateTimeImmutable();

        $order = new Order();
        $order->setUuid(Uuid::uuid4()->toString());
        $order->setTable(null);
        $order->setAdults(0);
        $order->setMinors(0);
        $order->setNotes('');
        $order->setTicketNumber($this->ordersRepository->getNextTicketNumber($now));
        $order->setCreatedAt($now);
        $order->setWaiter($waiter);
        $order->setEmployee(null);
        $order->setReservation(null);

        if ($markAsPaid) {
            $order->setStatus('CLOSED');
            $order->setPaidAt($now);
        } else {
            $order->setStatus('OPEN');
            $order->setPaidAt(null);
        }

        $orderEntryGroup = new OrderEntryGroup();
        $orderEntryGroup->setCreatedAt($now);
        $orderEntryGroup->setNotes($groupNotes);
        $orderEntryGroup->setOrder($order);

        $orderEntries = [];
        foreach ($entrySpecs as $spec) {
            $menuItem = $spec['menuItem'];

            if ($menuItem->getTrackAvailableQuantity()) {
                $menuItem->setAvailableQuantity($menuItem->getAvailableQuantity() - $spec['quantity']);
                $this->menuItemsRepository->persist($menuItem);
            }

            $orderEntry = new OrderEntry();
            $orderEntry->setDiscount(0);
            $orderEntry->setOrder($order);
            $orderEntry->setMenuItem($menuItem);
            $orderEntry->setMenuItemPrice($spec['menuItemPrice']);
            $orderEntry->setQuantity($spec['quantity']);
            $orderEntry->setFamily(1);
            $orderEntry->setTiming($spec['timing']);
            $orderEntry->setNotes($spec['notes']);
            $orderEntry->setIsPaid($markAsPaid);
            $orderEntry->setOrderEntryGroup($orderEntryGroup);
            $orderEntry->setWeight($spec['weight']);

            $orderEntryExtras = [];
            foreach ($spec['extras'] as $extra) {
                $orderEntryExtra = new OrderEntryExtra();
                $orderEntryExtra->setName($extra['name']);
                $orderEntryExtra->setPrice($extra['price']);
                $orderEntryExtra->setOrderEntry($orderEntry);
                $orderEntryExtras[] = $orderEntryExtra;
            }
            $orderEntry->setOrderEntryExtras($orderEntryExtras);

            $orderEntries[] = $orderEntry;
        }

        $order->setOrderEntries($orderEntries);
        $order->setOrderEntryGroups([$orderEntryGroup]);

        $this->ordersRepository->persist($order);

        $ecrJob = new EcrJob();
        $ecrJob->setOrder($order);
        $ecrJob->setStatus('pending');
        $ecrJob->setAttempts(0);
        $ecrJob->setCreatedAt(new DateTimeImmutable());
        $this->ecrJobsRepository->persist($ecrJob);

        return $order;
    }
}
```

Note: the original `CreateOrder` calls `$order->setPaidAt($now)` a second time after the entries loop when `markAsPaid` is set — that is redundant (already set at the top) and intentionally dropped here.

- [ ] **Step 2: Refactor the staff CreateOrder action to use the factory**

Replace the entire contents of `src/Application/Actions/TakeOutApp/CreateOrder.php` with:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\TakeOutApp;

use Application\Services\TakeOutOrderFactory;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateOrder
{
    public function __construct(
        private Twig $twig,
        private MenuItemsRepository $menuItemsRepository,
        private UsersRepository $usersRepository,
        private TakeOutOrderFactory $takeOutOrderFactory
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        if ($request->getMethod() === 'POST') {
            $requestData = json_decode(file_get_contents('php://input'), true);

            $waiter = $this->usersRepository->find($_SESSION['user']->getId());

            $entrySpecs = [];
            foreach ($requestData['orderEntries'] as $entry) {
                $menuItem = $this->menuItemsRepository->find($entry['menuItem']['id']);

                $extras = [];
                foreach ($entry['orderEntryExtras'] as $extra) {
                    $extras[] = [
                        'name' => $extra['name'],
                        'price' => floatval($extra['price']),
                    ];
                }

                $entrySpecs[] = [
                    'menuItem' => $menuItem,
                    'menuItemPrice' => $menuItem->getPrice(),
                    'quantity' => intval($entry['quantity']),
                    'timing' => intval($entry['timing'] ?? 1),
                    'notes' => $entry['notes'] ?? '',
                    'weight' => isset($entry['weight']) ? intval($entry['weight']) : null,
                    'extras' => $extras,
                ];
            }

            $order = $this->takeOutOrderFactory->create(
                $entrySpecs,
                $requestData['notes'],
                $waiter,
                !empty($requestData['markAsPaid'])
            );

            $response->getBody()->write(json_encode([
                'ticketNumber' => $order->getTicketNumber(),
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        return $this->twig->render($response, 'take_out_app/create_order.twig');
    }
}
```

- [ ] **Step 3: Bind the factory in the DI container**

In `app/dependencies.php`, add to the imports block (next to `use Application\Services\OrdersReportService;`):

```php
use Application\Services\TakeOutOrderFactory;
```

And add a definition after the `ScansReportService::class` entry:

```php
TakeOutOrderFactory::class => function (ContainerInterface $c) {
    return new TakeOutOrderFactory(
        $c->get(EcrJobsRepository::class),
        $c->get(MenuItemsRepository::class),
        $c->get(OrdersRepository::class)
    );
},
```

- [ ] **Step 4: Lint**

Run:
```bash
ddev exec php -l src/Application/Services/TakeOutOrderFactory.php
ddev exec php -l src/Application/Actions/TakeOutApp/CreateOrder.php
ddev exec php -l app/dependencies.php
```
Expected: `No syntax errors detected` for each.

- [ ] **Step 5: Manually verify the staff flow still works**

In a browser, log in at `https://sop.ddev.site/login` as a webmaster user, go to `/take-out/create`, add an item, save (receipt button). Expected: redirected to `/take-out/`, new order appears with a ticket number.

Then confirm the ECR job was created:
```bash
ddev exec 'psql -U db -d db -h db -c "SELECT id, status FROM ecr_jobs ORDER BY id DESC LIMIT 1"'
```
Expected: a `pending` row just created.

- [ ] **Step 6: Commit**

```bash
git add src/Application/Services/TakeOutOrderFactory.php src/Application/Actions/TakeOutApp/CreateOrder.php app/dependencies.php
git commit -m "Extract take-out order creation into TakeOutOrderFactory"
```

---

### Task 4: Public routes, Menu action, customer skeleton + menu page

**Files:**
- Create: `src/Application/Actions/CustomerSite/Menu.php`
- Create: `src/templates/customer_site/skeleton.twig`
- Create: `src/templates/customer_site/menu.twig`
- Modify: `app/routes.php`

- [ ] **Step 1: Create the Menu action**

`src/Application/Actions/CustomerSite/Menu.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\CustomerSite;

use Domain\Enums\MenuType;
use Domain\Repositories\MenusRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Menu
{
    public function __construct(
        private Twig $twig,
        private MenusRepository $menusRepository
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $menu = $this->menusRepository->findOneBy([
            'menuType' => MenuType::TakeOut,
            'isActive' => true,
        ]);

        $sections = [];
        if ($menu !== null) {
            foreach ($menu->getMenuSections() as $menuSection) {
                if (!$menuSection->getIsActive()) {
                    continue;
                }

                $items = [];
                foreach ($menuSection->getActiveMenuItems() as $menuItem) {
                    // Weight-priced items need a scale; the customer total must be exact
                    if ($menuItem->getPriceUnit() === 'kg') {
                        continue;
                    }

                    $extras = [];
                    foreach ($menuItem->getAllExtras() as $extra) {
                        $extras[] = [
                            'name' => $extra->getName(),
                            'price' => $extra->getPrice(),
                        ];
                    }

                    $items[] = [
                        'id' => $menuItem->getId(),
                        'price' => $menuItem->getPrice(),
                        'soldOut' => $menuItem->getTrackAvailableQuantity()
                            && ($menuItem->getAvailableQuantity() ?? 0) <= 0,
                        'names' => [
                            'el' => $menuItem->getTranslation('el')?->getName() ?? '',
                            'en' => $menuItem->getTranslation('en')?->getName() ?? '',
                        ],
                        'extras' => $extras,
                    ];
                }

                if (count($items) === 0) {
                    continue;
                }

                $sections[] = [
                    'id' => $menuSection->getId(),
                    'names' => [
                        'el' => $menuSection->getTranslation('el')?->getName() ?? '',
                        'en' => $menuSection->getTranslation('en')?->getName() ?? '',
                    ],
                    'items' => $items,
                ];
            }
        }

        return $this->twig->render($response, 'customer_site/menu.twig', [
            'sectionsJson' => json_encode($sections),
        ]);
    }
}
```

- [ ] **Step 2: Create the customer skeleton template**

`src/templates/customer_site/skeleton.twig`:

```twig
<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <title>{{siteName}} · Take Out</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    {% block pageCss %}{% endblock %}
</head>
<body class="bg-light">
    {% block content %}{% endblock %}
    {% block javascript %}{% endblock %}
</body>
</html>
```

- [ ] **Step 3: Create the menu page template**

`src/templates/customer_site/menu.twig`:

```twig
{% extends 'customer_site/skeleton.twig' %}

{% block pageCss %}
<style>
    .chips { overflow-x: auto; white-space: nowrap; -webkit-overflow-scrolling: touch; }
    .chips .btn { border-radius: 1rem; }
    .cart-bar { position: fixed; bottom: 0; left: 0; right: 0; z-index: 1040; }
    .cart-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1050; overflow-y: auto; }
    .cart-panel { background: #fff; border-radius: 1rem 1rem 0 0; margin-top: 20vh; min-height: 80vh; }
    main { padding-bottom: 6rem; }
</style>
{% endblock %}

{% block content %}
<div id="app" v-cloak>
    <nav class="navbar bg-success sticky-top">
        <div class="container-fluid">
            <span class="navbar-brand text-white fw-bold">{{siteName}} · Take Out</span>
            <button class="btn btn-sm btn-outline-light" @click="toggleLang" v-text="lang == 'el' ? 'EN' : 'ΕΛ'"></button>
        </div>
    </nav>
{% verbatim %}
    <div class="chips bg-white border-bottom px-2 py-2 sticky-top" style="top: 56px;">
        <button
            v-for="section in sections"
            :key="section.id"
            class="btn btn-sm btn-outline-success me-1"
            @click="scrollToSection(section.id)"
        >{{sectionName(section)}}</button>
    </div>

    <main class="container py-3">
        <div v-if="activeStatusUrl" class="alert alert-info py-2">
            <a :href="activeStatusUrl" class="text-decoration-none">{{t.activeOrder}} →</a>
        </div>

        <div v-if="sections.length == 0" class="text-center text-muted py-5">{{t.menuUnavailable}}</div>

        <section v-for="section in sections" :key="section.id" :id="'section-' + section.id" class="mb-4">
            <h2 class="fs-4 border-bottom pb-2">{{sectionName(section)}}</h2>
            <div
                v-for="item in section.items"
                :key="item.id"
                class="d-flex justify-content-between align-items-center bg-white rounded border p-2 mb-2"
            >
                <div>
                    <div class="fw-semibold">{{itemName(item)}}</div>
                    <div class="text-muted">&euro;{{item.price.toFixed(2)}}</div>
                    <span v-if="item.soldOut" class="badge bg-secondary">{{t.soldOut}}</span>
                </div>
                <div class="text-end">
                    <div v-if="cartQuantity(item) > 0" class="btn-group">
                        <button class="btn btn-outline-success" @click="removeOne(item)">−</button>
                        <span class="btn btn-outline-success disabled">{{cartQuantity(item)}}</span>
                        <button class="btn btn-success" @click="addItem(item)" :disabled="item.soldOut">+</button>
                    </div>
                    <button v-else class="btn btn-success" @click="addItem(item)" :disabled="item.soldOut">+</button>
                </div>
            </div>
        </section>
    </main>

    <div class="cart-bar p-2" v-if="cart.length > 0 && !cartOpen">
        <button class="btn btn-success btn-lg w-100" @click="cartOpen = true">
            {{t.viewCart}} · {{cartCount}} · &euro;{{cartTotal.toFixed(2)}}
        </button>
    </div>

    <div class="cart-overlay" v-if="cartOpen" @click.self="cartOpen = false">
        <div class="cart-panel container p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fs-4 m-0">{{t.yourOrder}}</h2>
                <button class="btn-close" @click="cartOpen = false"></button>
            </div>

            <div v-for="(line, index) in cart" :key="lineKey(line)" class="border-bottom pb-2 mb-2">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="fw-semibold">{{line.quantity}}× {{lineName(line)}}</div>
                        <div class="text-muted small" v-if="line.extras.length > 0">+ {{line.extras.join(', ')}}</div>
                    </div>
                    <div class="text-end">
                        <div>&euro;{{lineTotal(line).toFixed(2)}}</div>
                        <div class="btn-group btn-group-sm mt-1">
                            <button class="btn btn-outline-secondary" @click="decrementLine(index)">−</button>
                            <button class="btn btn-outline-secondary" @click="incrementLine(index)">+</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                <span>{{t.total}}</span><span>&euro;{{cartTotal.toFixed(2)}}</span>
            </div>

            <input class="form-control mb-2" v-model.trim="customerName" :placeholder="t.name" maxlength="100">
            <input class="form-control mb-2" v-model.trim="customerPhone" :placeholder="t.phone" maxlength="20" inputmode="tel">
            <textarea class="form-control mb-2" v-model.trim="notes" :placeholder="t.noteForKitchen" rows="2" maxlength="500"></textarea>
            <input type="text" v-model="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">

            <div class="alert alert-warning py-2">{{t.payAtPickupNotice}}</div>
            <div class="alert alert-danger py-2" v-if="errorMessage">{{errorMessage}}</div>

            <button class="btn btn-success btn-lg w-100" @click="submit" :disabled="sending || cart.length == 0">
                <span class="spinner-border spinner-border-sm me-1" v-if="sending"></span>{{t.sendOrder}}
            </button>
        </div>
    </div>

    <div class="modal d-block" tabindex="-1" v-if="extrasModalItem" style="background: rgba(0,0,0,.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{itemName(extrasModalItem)}}</h5>
                    <button type="button" class="btn-close" @click="extrasModalItem = null"></button>
                </div>
                <div class="modal-body">
                    <div class="form-check" v-for="extra in extrasModalItem.extras" :key="extra.name">
                        <input class="form-check-input" type="checkbox" :value="extra.name" v-model="selectedExtras" :id="'extra-' + extra.name">
                        <label class="form-check-label d-flex justify-content-between" :for="'extra-' + extra.name">
                            <span>{{extra.name}}</span><span>+&euro;{{extra.price.toFixed(2)}}</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="extrasModalItem = null">{{t.cancel}}</button>
                    <button class="btn btn-success" @click="confirmExtras">{{t.add}}</button>
                </div>
            </div>
        </div>
    </div>
{% endverbatim %}
</div>
{% endblock %}

{% block javascript %}
<script src="/assets/js/vue.global.prod.js"></script>
<script src="/assets/js/axios.min.js"></script>
<script>
    const SECTIONS = {{ sectionsJson|raw }};

    const I18N = {
        el: {
            viewCart: 'Δείτε την παραγγελία', yourOrder: 'Η παραγγελία σας', total: 'Σύνολο',
            name: 'Όνομα', phone: 'Τηλέφωνο', noteForKitchen: 'Σχόλιο για την κουζίνα (προαιρετικό)',
            payAtPickupNotice: 'Πληρώνετε κατά την παραλαβή. Το κατάστημα θα επιβεβαιώσει την παραγγελία σας με εκτιμώμενο χρόνο.',
            sendOrder: 'Αποστολή παραγγελίας', soldOut: 'Εξαντλήθηκε', add: 'Προσθήκη', cancel: 'Άκυρο',
            activeOrder: 'Έχετε ενεργή παραγγελία', menuUnavailable: 'Ο κατάλογος δεν είναι διαθέσιμος αυτή τη στιγμή.',
            errorGeneric: 'Κάτι πήγε στραβά. Δοκιμάστε ξανά.',
            errorName: 'Συμπληρώστε το όνομά σας.', errorPhone: 'Συμπληρώστε ένα έγκυρο τηλέφωνο.'
        },
        en: {
            viewCart: 'View order', yourOrder: 'Your order', total: 'Total',
            name: 'Name', phone: 'Phone', noteForKitchen: 'Note for the kitchen (optional)',
            payAtPickupNotice: 'You pay at pickup. The restaurant will confirm your order with an estimated time.',
            sendOrder: 'Send order', soldOut: 'Sold out', add: 'Add', cancel: 'Cancel',
            activeOrder: 'You have an active order', menuUnavailable: 'The menu is currently unavailable.',
            errorGeneric: 'Something went wrong. Please try again.',
            errorName: 'Please enter your name.', errorPhone: 'Please enter a valid phone number.'
        }
    };

    Vue.createApp({
        data() {
            return {
                lang: localStorage.getItem('takeOutLang') || 'el',
                sections: SECTIONS,
                cart: JSON.parse(localStorage.getItem('takeOutCart') || '[]'),
                cartOpen: false,
                customerName: localStorage.getItem('takeOutName') || '',
                customerPhone: localStorage.getItem('takeOutPhone') || '',
                notes: '',
                website: '',
                sending: false,
                errorMessage: '',
                extrasModalItem: null,
                selectedExtras: [],
                activeStatusUrl: null
            }
        },
        computed: {
            t() { return I18N[this.lang]; },
            cartCount() { return this.cart.reduce((sum, line) => sum + line.quantity, 0); },
            cartTotal() { return this.cart.reduce((sum, line) => sum + this.lineTotal(line), 0); }
        },
        watch: {
            cart: { deep: true, handler(value) { localStorage.setItem('takeOutCart', JSON.stringify(value)); } }
        },
        mounted() {
            const token = localStorage.getItem('takeOutToken');
            if (token) {
                axios.get('/order/status/' + token + '/poll')
                    .then(response => {
                        if (response.data.status === 'PENDING' || response.data.status === 'ACCEPTED') {
                            this.activeStatusUrl = '/order/status/' + token;
                        }
                    })
                    .catch(() => localStorage.removeItem('takeOutToken'));
            }
        },
        methods: {
            toggleLang() {
                this.lang = this.lang === 'el' ? 'en' : 'el';
                localStorage.setItem('takeOutLang', this.lang);
            },
            sectionName(section) { return section.names[this.lang] || section.names.el || section.names.en; },
            itemName(item) { return item.names[this.lang] || item.names.el || item.names.en; },
            findItem(id) {
                for (const section of this.sections) {
                    const item = section.items.find(i => i.id === id);
                    if (item) return item;
                }
                return null;
            },
            lineName(line) {
                const item = this.findItem(line.id);
                return item ? this.itemName(item) : '';
            },
            lineKey(line) { return line.id + '|' + line.extras.join(','); },
            lineTotal(line) {
                const item = this.findItem(line.id);
                if (!item) return 0;
                let price = item.price;
                for (const name of line.extras) {
                    const extra = item.extras.find(e => e.name === name);
                    if (extra) price += extra.price;
                }
                return line.quantity * price;
            },
            cartQuantity(item) {
                return this.cart.filter(line => line.id === item.id).reduce((sum, line) => sum + line.quantity, 0);
            },
            addItem(item) {
                if (item.extras.length > 0) {
                    this.extrasModalItem = item;
                    this.selectedExtras = [];
                } else {
                    this.pushLine(item, []);
                }
            },
            confirmExtras() {
                this.pushLine(this.extrasModalItem, [...this.selectedExtras].sort());
                this.extrasModalItem = null;
            },
            pushLine(item, extras) {
                const key = item.id + '|' + extras.join(',');
                const line = this.cart.find(l => this.lineKey(l) === key);
                if (line && line.quantity < 20) {
                    line.quantity++;
                } else if (!line) {
                    this.cart.push({ id: item.id, quantity: 1, extras });
                }
            },
            removeOne(item) {
                for (let i = this.cart.length - 1; i >= 0; i--) {
                    if (this.cart[i].id === item.id) { this.decrementLine(i); return; }
                }
            },
            incrementLine(index) {
                if (this.cart[index].quantity < 20) this.cart[index].quantity++;
            },
            decrementLine(index) {
                this.cart[index].quantity--;
                if (this.cart[index].quantity <= 0) this.cart.splice(index, 1);
            },
            scrollToSection(id) {
                document.getElementById('section-' + id).scrollIntoView({ behavior: 'smooth' });
            },
            submit() {
                this.errorMessage = '';
                if (this.customerName.length === 0) { this.errorMessage = this.t.errorName; return; }
                if (!/^\+?[0-9 ]{8,20}$/.test(this.customerPhone)) { this.errorMessage = this.t.errorPhone; return; }

                this.sending = true;
                axios.post('/order/submit', {
                    customerName: this.customerName,
                    customerPhone: this.customerPhone,
                    notes: this.notes,
                    website: this.website,
                    entries: this.cart.map(line => ({
                        menuItemId: line.id,
                        quantity: line.quantity,
                        extras: line.extras
                    }))
                }).then(response => {
                    localStorage.setItem('takeOutToken', response.data.statusUrl.split('/').pop());
                    localStorage.setItem('takeOutCart', '[]');
                    localStorage.setItem('takeOutName', this.customerName);
                    localStorage.setItem('takeOutPhone', this.customerPhone);
                    window.location.href = response.data.statusUrl;
                }).catch(error => {
                    this.sending = false;
                    this.errorMessage = error.response?.data?.error || this.t.errorGeneric;
                });
            }
        }
    }).mount('#app');
</script>
{% endblock %}
```

- [ ] **Step 4: Register the public route group**

In `app/routes.php`:

Add to the imports block (after the `TakeOutAppPayment` import at line 112):

```php
use Application\Actions\CustomerSite\Menu as CustomerSiteMenu;
```

Add the group right before the `$app->group('/api', ...)` block (line 311), with **no** auth middleware:

```php
    $app->group('/order', function (RouteCollectorProxy $group) {
        $group->get('/', CustomerSiteMenu::class);
    });
```

(The `/submit` and `/status` routes are added in Tasks 5 and 6.)

- [ ] **Step 5: Lint and verify the page is publicly reachable**

Run:
```bash
ddev exec php -l src/Application/Actions/CustomerSite/Menu.php
ddev exec php -l app/routes.php
curl -k -s -o /dev/null -w "%{http_code}\n" https://sop.ddev.site/order/
```
Expected: no syntax errors; HTTP `200` (no login redirect, no session cookie needed).

Then open `https://sop.ddev.site/order/` in a browser (no login): the Take Out menu renders with category chips, + buttons add items, the sticky cart bar appears, the EL/EN toggle switches names, and the cart survives a page refresh.

- [ ] **Step 6: Commit**

```bash
git add src/Application/Actions/CustomerSite/Menu.php src/templates/customer_site/skeleton.twig src/templates/customer_site/menu.twig app/routes.php
git commit -m "Add public customer ordering page for take-out"
```

---

### Task 5: SubmitRequest action

**Files:**
- Create: `src/Application/Actions/CustomerSite/SubmitRequest.php`
- Modify: `app/routes.php`

- [ ] **Step 1: Create the action**

`src/Application/Actions/CustomerSite/SubmitRequest.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\CustomerSite;

use DateTimeImmutable;
use Domain\Entities\TakeOutRequest;
use Domain\Entities\TakeOutRequestEntry;
use Domain\Entities\TakeOutRequestEntryExtra;
use Domain\Enums\MenuType;
use Domain\Enums\TakeOutRequestStatus;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\MenusRepository;
use Domain\Repositories\TakeOutRequestsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;

final class SubmitRequest
{
    public function __construct(
        private MenusRepository $menusRepository,
        private MenuItemsRepository $menuItemsRepository,
        private TakeOutRequestsRepository $takeOutRequestsRepository
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $data = json_decode((string) $request->getBody(), true);

        if (!is_array($data) || !empty($data['website'])) {
            return $this->fail($response, 'Invalid request.', 400);
        }

        $name = trim($data['customerName'] ?? '');
        $phone = trim($data['customerPhone'] ?? '');
        $notes = trim($data['notes'] ?? '');
        $entries = $data['entries'] ?? [];

        if ($name === '' || mb_strlen($name) > 100) {
            return $this->fail($response, 'Please enter your name.', 422);
        }
        if (!preg_match('/^\+?[0-9 ]{8,20}$/', $phone)) {
            return $this->fail($response, 'Please enter a valid phone number.', 422);
        }
        if (!is_array($entries) || count($entries) === 0 || count($entries) > 30) {
            return $this->fail($response, 'Your cart is empty.', 422);
        }

        if ($this->takeOutRequestsRepository->countPendingByPhone($phone) >= 3) {
            return $this->fail($response, 'Too many pending orders for this phone number.', 429);
        }

        $menu = $this->menusRepository->findOneBy([
            'menuType' => MenuType::TakeOut,
            'isActive' => true,
        ]);
        if ($menu === null) {
            return $this->fail($response, 'Ordering is currently unavailable.', 503);
        }

        $takeOutRequest = new TakeOutRequest();
        $takeOutRequest->setCreatedAt(new DateTimeImmutable());
        $takeOutRequest->setToken(Uuid::uuid4()->toString());
        $takeOutRequest->setCustomerName($name);
        $takeOutRequest->setCustomerPhone($phone);
        $takeOutRequest->setNotes(mb_substr($notes, 0, 500));
        $takeOutRequest->setStatus(TakeOutRequestStatus::Pending);
        $takeOutRequest->setEtaMinutes(null);
        $takeOutRequest->setRespondedAt(null);
        $takeOutRequest->setOrder(null);

        $requestEntries = [];
        foreach ($entries as $entry) {
            $quantity = (int) ($entry['quantity'] ?? 0);
            if ($quantity < 1 || $quantity > 20) {
                return $this->fail($response, 'Invalid quantity.', 422);
            }

            $menuItem = $this->menuItemsRepository->find((int) ($entry['menuItemId'] ?? 0));
            if ($menuItem === null
                || !$menuItem->getIsActive()
                || $menuItem->getPriceUnit() === 'kg'
                || $menuItem->getMenuSection()->getMenu()->getId() !== $menu->getId()
            ) {
                return $this->fail($response, 'Some items in your cart are no longer available.', 409);
            }
            if ($menuItem->getTrackAvailableQuantity() && ($menuItem->getAvailableQuantity() ?? 0) < $quantity) {
                return $this->fail($response, 'Some items in your cart are sold out.', 409);
            }

            $requestEntry = new TakeOutRequestEntry();
            $requestEntry->setRequest($takeOutRequest);
            $requestEntry->setMenuItem($menuItem);
            $requestEntry->setMenuItemPrice($menuItem->getPrice());
            $requestEntry->setQuantity($quantity);

            $availableExtras = [];
            foreach ($menuItem->getAllExtras() as $extra) {
                $availableExtras[$extra->getName()] = $extra->getPrice();
            }

            $requestExtras = [];
            foreach ($entry['extras'] ?? [] as $extraName) {
                if (!is_string($extraName) || !array_key_exists($extraName, $availableExtras)) {
                    return $this->fail($response, 'Some extras are no longer available.', 409);
                }
                $requestExtra = new TakeOutRequestEntryExtra();
                $requestExtra->setEntry($requestEntry);
                $requestExtra->setName($extraName);
                $requestExtra->setPrice($availableExtras[$extraName]);
                $requestExtras[] = $requestExtra;
            }
            $requestEntry->setExtras($requestExtras);

            $requestEntries[] = $requestEntry;
        }

        $takeOutRequest->setEntries($requestEntries);
        $this->takeOutRequestsRepository->persist($takeOutRequest);

        $response->getBody()->write(json_encode([
            'statusUrl' => '/order/status/' . $takeOutRequest->getToken(),
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function fail(Response $response, string $message, int $status): Response
    {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
```

- [ ] **Step 2: Register the route**

In `app/routes.php`, add the import next to `CustomerSiteMenu`:

```php
use Application\Actions\CustomerSite\SubmitRequest as CustomerSiteSubmitRequest;
```

And inside the `/order` group, after the `GET /`:

```php
        $group->post('/submit', CustomerSiteSubmitRequest::class);
```

- [ ] **Step 3: Lint**

Run:
```bash
ddev exec php -l src/Application/Actions/CustomerSite/SubmitRequest.php
ddev exec php -l app/routes.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 4: Verify with curl**

Find a real take-out menu item id:
```bash
ddev exec 'psql -U db -d db -h db -c "SELECT mi.id FROM menu_items mi JOIN menu_sections ms ON mi.menu_section_id = ms.id JOIN menus m ON ms.menu_id = m.id WHERE m.menu_type = '\''take_out'\'' AND mi.is_active LIMIT 1"'
```

Then submit (replace `ITEM_ID`):
```bash
curl -k -s -X POST https://sop.ddev.site/order/submit \
  -H 'Content-Type: application/json' \
  -d '{"customerName":"Test Customer","customerPhone":"2101234567","notes":"test","website":"","entries":[{"menuItemId":ITEM_ID,"quantity":2,"extras":[]}]}'
```
Expected: `{"statusUrl":"/order/status/<uuid>"}`.

Negative checks:
```bash
# honeypot filled -> 400
curl -k -s -o /dev/null -w "%{http_code}\n" -X POST https://sop.ddev.site/order/submit -H 'Content-Type: application/json' -d '{"customerName":"T","customerPhone":"2101234567","website":"spam","entries":[{"menuItemId":1,"quantity":1,"extras":[]}]}'
# bad phone -> 422
curl -k -s -o /dev/null -w "%{http_code}\n" -X POST https://sop.ddev.site/order/submit -H 'Content-Type: application/json' -d '{"customerName":"T","customerPhone":"abc","website":"","entries":[{"menuItemId":1,"quantity":1,"extras":[]}]}'
```

Then submit two more valid requests with the same phone — the 4th overall must return `429`.

Confirm the rows landed:
```bash
ddev exec 'psql -U db -d db -h db -c "SELECT id, customer_name, status FROM take_out_requests ORDER BY id DESC LIMIT 3"'
```

- [ ] **Step 5: Commit**

```bash
git add src/Application/Actions/CustomerSite/SubmitRequest.php app/routes.php
git commit -m "Add public take-out order submission endpoint"
```

---

### Task 6: Status page and poll endpoint

**Files:**
- Create: `src/Application/Actions/CustomerSite/Status.php`
- Create: `src/Application/Actions/CustomerSite/StatusPoll.php`
- Create: `src/templates/customer_site/status.twig`
- Modify: `app/routes.php`

- [ ] **Step 1: Create the Status action**

`src/Application/Actions/CustomerSite/Status.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\CustomerSite;

use Domain\Repositories\TakeOutRequestsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;

final class Status
{
    public function __construct(
        private Twig $twig,
        private TakeOutRequestsRepository $takeOutRequestsRepository
    ) {}

    public function __invoke(Request $request, Response $response, array $args)
    {
        $takeOutRequest = $this->takeOutRequestsRepository->findOneByToken($args['token']);

        if ($takeOutRequest === null) {
            throw new HttpNotFoundException($request);
        }

        $entries = [];
        foreach ($takeOutRequest->getEntries() as $entry) {
            $extras = [];
            foreach ($entry->getExtras() as $extra) {
                $extras[] = $extra->getName();
            }
            $entries[] = [
                'quantity' => $entry->getQuantity(),
                'names' => [
                    'el' => $entry->getMenuItem()->getTranslation('el')?->getName() ?? '',
                    'en' => $entry->getMenuItem()->getTranslation('en')?->getName() ?? '',
                ],
                'extras' => $extras,
                'price' => $entry->getPrice(),
            ];
        }

        return $this->twig->render($response, 'customer_site/status.twig', [
            'token' => $takeOutRequest->getToken(),
            'customerName' => $takeOutRequest->getCustomerName(),
            'stateJson' => json_encode([
                'status' => $takeOutRequest->getStatus()->value,
                'etaMinutes' => $takeOutRequest->getEtaMinutes(),
                'ticketNumber' => $takeOutRequest->getOrder()?->getTicketNumber(),
                'entries' => $entries,
                'total' => $takeOutRequest->getTotal(),
            ]),
        ]);
    }
}
```

- [ ] **Step 2: Create the StatusPoll action**

`src/Application/Actions/CustomerSite/StatusPoll.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\CustomerSite;

use Domain\Repositories\TakeOutRequestsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

final class StatusPoll
{
    public function __construct(
        private TakeOutRequestsRepository $takeOutRequestsRepository
    ) {}

    public function __invoke(Request $request, Response $response, array $args)
    {
        $takeOutRequest = $this->takeOutRequestsRepository->findOneByToken($args['token']);

        if ($takeOutRequest === null) {
            throw new HttpNotFoundException($request);
        }

        $response->getBody()->write(json_encode([
            'status' => $takeOutRequest->getStatus()->value,
            'etaMinutes' => $takeOutRequest->getEtaMinutes(),
            'ticketNumber' => $takeOutRequest->getOrder()?->getTicketNumber(),
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

- [ ] **Step 3: Create the status template**

`src/templates/customer_site/status.twig`:

```twig
{% extends 'customer_site/skeleton.twig' %}

{% block content %}
<div id="app" v-cloak>
    <nav class="navbar bg-success sticky-top">
        <div class="container-fluid">
            <a href="/order/" class="navbar-brand text-white fw-bold text-decoration-none">{{siteName}} · Take Out</a>
            <button class="btn btn-sm btn-outline-light" @click="toggleLang" v-text="lang == 'el' ? 'EN' : 'ΕΛ'"></button>
        </div>
    </nav>

    <main class="container py-4" style="max-width: 480px;">
{% verbatim %}
        <div class="card text-center">
            <div class="card-body">
                <template v-if="state.status === 'PENDING'">
                    <div class="display-3">⏳</div>
                    <h1 class="fs-4">{{t.waitingTitle}}</h1>
                    <p class="text-muted">{{t.waitingSubtitle}}</p>
                </template>
                <template v-else-if="state.status === 'ACCEPTED'">
                    <div class="display-3">✅</div>
                    <h1 class="fs-4 text-success">{{t.acceptedTitle}}</h1>
                    <p class="fs-5" v-if="state.etaMinutes">{{t.readyInAbout}} <b>{{state.etaMinutes}} {{t.minutes}}</b></p>
                    <p class="fs-5" v-if="state.ticketNumber">{{t.ticket}} <b>#{{state.ticketNumber}}</b></p>
                    <p class="text-muted">{{t.payAtPickup}}</p>
                </template>
                <template v-else>
                    <div class="display-3">😞</div>
                    <h1 class="fs-4">{{t.rejectedTitle}}</h1>
                    <p class="text-muted">{{t.rejectedSubtitle}}</p>
                </template>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div v-for="entry in state.entries" class="d-flex justify-content-between border-bottom py-1">
                    <span>
                        {{entry.quantity}}× {{entryName(entry)}}
                        <span class="text-muted small" v-if="entry.extras.length > 0"><br>+ {{entry.extras.join(', ')}}</span>
                    </span>
                    <span>&euro;{{entry.price.toFixed(2)}}</span>
                </div>
                <div class="d-flex justify-content-between fw-bold pt-2">
                    <span>{{t.total}}</span><span>&euro;{{state.total.toFixed(2)}}</span>
                </div>
            </div>
        </div>
{% endverbatim %}
    </main>
</div>
{% endblock %}

{% block javascript %}
<script src="/assets/js/vue.global.prod.js"></script>
<script src="/assets/js/axios.min.js"></script>
<script>
    const TOKEN = {{ token|json_encode|raw }};
    const STATE = {{ stateJson|raw }};

    const I18N = {
        el: {
            waitingTitle: 'Αναμονή επιβεβαίωσης…', waitingSubtitle: 'Το κατάστημα θα επιβεβαιώσει την παραγγελία σας σύντομα.',
            acceptedTitle: 'Η παραγγελία έγινε δεκτή!', readyInAbout: 'Έτοιμη σε περίπου', minutes: 'λεπτά',
            ticket: 'Απόδειξη', payAtPickup: 'Πληρώνετε κατά την παραλαβή.',
            rejectedTitle: 'Η παραγγελία δεν έγινε δεκτή', rejectedSubtitle: 'Παρακαλούμε τηλεφωνήστε στο κατάστημα.',
            total: 'Σύνολο'
        },
        en: {
            waitingTitle: 'Waiting for the restaurant…', waitingSubtitle: 'The restaurant will confirm your order shortly.',
            acceptedTitle: 'Order accepted!', readyInAbout: 'Ready in about', minutes: 'min',
            ticket: 'Ticket', payAtPickup: 'You pay at pickup.',
            rejectedTitle: "The restaurant couldn't take your order", rejectedSubtitle: 'Please call the restaurant.',
            total: 'Total'
        }
    };

    Vue.createApp({
        data() {
            return {
                lang: localStorage.getItem('takeOutLang') || 'el',
                state: STATE,
                pollTimer: null
            }
        },
        computed: {
            t() { return I18N[this.lang]; }
        },
        mounted() {
            if (this.state.status === 'PENDING') {
                this.pollTimer = setInterval(() => this.poll(), 10000);
            }
            if (this.state.status === 'REJECTED') {
                localStorage.removeItem('takeOutToken');
            }
        },
        methods: {
            toggleLang() {
                this.lang = this.lang === 'el' ? 'en' : 'el';
                localStorage.setItem('takeOutLang', this.lang);
            },
            entryName(entry) { return entry.names[this.lang] || entry.names.el || entry.names.en; },
            poll() {
                axios.get('/order/status/' + TOKEN + '/poll').then(response => {
                    this.state.status = response.data.status;
                    this.state.etaMinutes = response.data.etaMinutes;
                    this.state.ticketNumber = response.data.ticketNumber;
                    if (this.state.status !== 'PENDING') {
                        clearInterval(this.pollTimer);
                        if (this.state.status === 'REJECTED') {
                            localStorage.removeItem('takeOutToken');
                        }
                    }
                });
            }
        }
    }).mount('#app');
</script>
{% endblock %}
```

- [ ] **Step 4: Register the routes**

In `app/routes.php`, add imports next to the other CustomerSite imports:

```php
use Application\Actions\CustomerSite\Status as CustomerSiteStatus;
use Application\Actions\CustomerSite\StatusPoll as CustomerSiteStatusPoll;
```

And inside the `/order` group:

```php
        $group->get('/status/{token}', CustomerSiteStatus::class);
        $group->get('/status/{token}/poll', CustomerSiteStatusPoll::class);
```

- [ ] **Step 5: Lint and verify**

Run:
```bash
ddev exec php -l src/Application/Actions/CustomerSite/Status.php
ddev exec php -l src/Application/Actions/CustomerSite/StatusPoll.php
ddev exec php -l app/routes.php
```

Grab the token from a request created in Task 5:
```bash
ddev exec 'psql -U db -d db -h db -c "SELECT token FROM take_out_requests ORDER BY id DESC LIMIT 1"'
curl -k -s -o /dev/null -w "%{http_code}\n" https://sop.ddev.site/order/status/<token>
curl -k -s https://sop.ddev.site/order/status/<token>/poll
curl -k -s -o /dev/null -w "%{http_code}\n" https://sop.ddev.site/order/status/not-a-real-token
```
Expected: `200`; `{"status":"PENDING","etaMinutes":null,"ticketNumber":null}`; `404`.

In a browser, open the status URL: waiting state with the order summary and totals, EL/EN toggle works.

- [ ] **Step 6: Commit**

```bash
git add src/Application/Actions/CustomerSite/Status.php src/Application/Actions/CustomerSite/StatusPoll.php src/templates/customer_site/status.twig app/routes.php
git commit -m "Add customer order status page with polling"
```

---

### Task 7: Staff side — pending queue in the take-out app

**Files:**
- Create: `src/Application/Actions/TakeOutApp/PendingRequests.php`
- Create: `src/Application/Actions/TakeOutApp/AcceptRequest.php`
- Create: `src/Application/Actions/TakeOutApp/RejectRequest.php`
- Modify: `src/templates/take_out_app/homepage.twig`
- Modify: `app/routes.php`

No permissions migration: the existing `/take-out` routes have no `user_permissions` rows (verified in the dev DB) and rely on the `webmaster` bypass — the new endpoints behave identically.

- [ ] **Step 1: Create the PendingRequests action**

`src/Application/Actions/TakeOutApp/PendingRequests.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\TakeOutApp;

use Domain\Repositories\TakeOutRequestsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class PendingRequests
{
    public function __construct(
        private TakeOutRequestsRepository $takeOutRequestsRepository
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $requests = [];
        foreach ($this->takeOutRequestsRepository->findPending() as $takeOutRequest) {
            $entries = [];
            foreach ($takeOutRequest->getEntries() as $entry) {
                $extras = [];
                foreach ($entry->getExtras() as $extra) {
                    $extras[] = $extra->getName();
                }
                $entries[] = [
                    'name' => $entry->getMenuItem()->getTranslation('el')?->getName() ?? '',
                    'quantity' => $entry->getQuantity(),
                    'extras' => $extras,
                    'price' => $entry->getPrice(),
                ];
            }

            $requests[] = [
                'id' => $takeOutRequest->getId(),
                'createdAt' => $takeOutRequest->getCreatedAt()->format('Y-m-d H:i:s'),
                'customerName' => $takeOutRequest->getCustomerName(),
                'customerPhone' => $takeOutRequest->getCustomerPhone(),
                'notes' => $takeOutRequest->getNotes(),
                'entries' => $entries,
                'total' => $takeOutRequest->getTotal(),
            ];
        }

        $response->getBody()->write(json_encode($requests));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

- [ ] **Step 2: Create the AcceptRequest action**

`src/Application/Actions/TakeOutApp/AcceptRequest.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\TakeOutApp;

use Application\Services\TakeOutOrderFactory;
use DateTimeImmutable;
use Domain\Enums\TakeOutRequestStatus;
use Domain\Repositories\TakeOutRequestsRepository;
use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AcceptRequest
{
    public function __construct(
        private TakeOutRequestsRepository $takeOutRequestsRepository,
        private UsersRepository $usersRepository,
        private TakeOutOrderFactory $takeOutOrderFactory
    ) {}

    public function __invoke(Request $request, Response $response, array $args)
    {
        $takeOutRequest = $this->takeOutRequestsRepository->find((int) $args['id']);

        if ($takeOutRequest === null) {
            return $this->fail($response, 'Request not found.', 404);
        }
        if ($takeOutRequest->getStatus() !== TakeOutRequestStatus::Pending) {
            return $this->fail($response, 'Request is no longer pending.', 409);
        }

        $requestData = json_decode((string) $request->getBody(), true);
        $etaMinutes = (int) ($requestData['etaMinutes'] ?? 0);
        if ($etaMinutes < 5 || $etaMinutes > 180) {
            return $this->fail($response, 'Invalid ETA.', 422);
        }

        foreach ($takeOutRequest->getEntries() as $entry) {
            $menuItem = $entry->getMenuItem();
            if ($menuItem->getTrackAvailableQuantity()
                && ($menuItem->getAvailableQuantity() ?? 0) < $entry->getQuantity()
            ) {
                return $this->fail(
                    $response,
                    'Not enough quantity for: ' . ($menuItem->getTranslation('el')?->getName() ?? $menuItem->getId()),
                    409
                );
            }
        }

        $entrySpecs = [];
        foreach ($takeOutRequest->getEntries() as $entry) {
            $extras = [];
            foreach ($entry->getExtras() as $extra) {
                $extras[] = [
                    'name' => $extra->getName(),
                    'price' => $extra->getPrice(),
                ];
            }

            $entrySpecs[] = [
                'menuItem' => $entry->getMenuItem(),
                'menuItemPrice' => $entry->getMenuItemPrice(),
                'quantity' => $entry->getQuantity(),
                'timing' => $entry->getMenuItem()->getIsDrink() ? 6 : 1,
                'notes' => '',
                'weight' => null,
                'extras' => $extras,
            ];
        }

        $waiter = $this->usersRepository->find($_SESSION['user']->getId());
        $order = $this->takeOutOrderFactory->create($entrySpecs, $takeOutRequest->getNotes(), $waiter, false);

        $takeOutRequest->setStatus(TakeOutRequestStatus::Accepted);
        $takeOutRequest->setEtaMinutes($etaMinutes);
        $takeOutRequest->setRespondedAt(new DateTimeImmutable());
        $takeOutRequest->setOrder($order);
        $this->takeOutRequestsRepository->persist($takeOutRequest);

        $response->getBody()->write(json_encode([
            'ticketNumber' => $order->getTicketNumber(),
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function fail(Response $response, string $message, int $status): Response
    {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
```

Note: `MenuItem::getIsDrink()` (verified at `src/Domain/Entities/MenuItem.php:168`) mirrors the staff app's timing convention — drinks get timing 6, food 1.

- [ ] **Step 3: Create the RejectRequest action**

`src/Application/Actions/TakeOutApp/RejectRequest.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\TakeOutApp;

use DateTimeImmutable;
use Domain\Enums\TakeOutRequestStatus;
use Domain\Repositories\TakeOutRequestsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class RejectRequest
{
    public function __construct(
        private TakeOutRequestsRepository $takeOutRequestsRepository
    ) {}

    public function __invoke(Request $request, Response $response, array $args)
    {
        $takeOutRequest = $this->takeOutRequestsRepository->find((int) $args['id']);

        if ($takeOutRequest === null) {
            $response->getBody()->write(json_encode(['error' => 'Request not found.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        if ($takeOutRequest->getStatus() !== TakeOutRequestStatus::Pending) {
            $response->getBody()->write(json_encode(['error' => 'Request is no longer pending.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $takeOutRequest->setStatus(TakeOutRequestStatus::Rejected);
        $takeOutRequest->setRespondedAt(new DateTimeImmutable());
        $this->takeOutRequestsRepository->persist($takeOutRequest);

        $response->getBody()->write('ok');
        return $response;
    }
}
```

- [ ] **Step 4: Register the staff routes**

In `app/routes.php`, add imports after the `TakeOutAppPayment` import:

```php
use Application\Actions\TakeOutApp\PendingRequests as TakeOutAppPendingRequests;
use Application\Actions\TakeOutApp\AcceptRequest as TakeOutAppAcceptRequest;
use Application\Actions\TakeOutApp\RejectRequest as TakeOutAppRejectRequest;
```

And inside the existing `/take-out` group, after the `payment` route:

```php
        $group->get('/requests/pending', TakeOutAppPendingRequests::class);
        $group->post('/requests/{id}/accept', TakeOutAppAcceptRequest::class);
        $group->post('/requests/{id}/reject', TakeOutAppRejectRequest::class);
```

- [ ] **Step 5: Add the pending section to the take-out homepage**

Replace the entire contents of `src/templates/take_out_app/homepage.twig` with:

```twig
{% extends 'app_skeleton.twig' %}

{% block content %}
<div id="app">
	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
	    <h1>
		    {% include 'partials/app_nav.twig' %} / <i class="bi bi-bag"></i>
		</h1>
	    <div class="btn-toolbar">
  			<a href="/take-out/create" class="btn btn-primary btn-lg"><i class="bi bi-plus-lg"></i></a>
	    </div>
	</div>
{% verbatim %}
	<div v-if="pendingRequests.length > 0" class="mb-4">
		<h2 class="fs-5 text-warning-emphasis"><i class="bi bi-globe"></i> Online παραγγελίες ({{pendingRequests.length}})</h2>
		<div v-for="request in pendingRequests" :key="request.id" class="card border-warning mb-2">
			<div class="card-body">
				<div class="d-flex justify-content-between flex-wrap">
					<div>
						<span class="fw-bold fs-5">{{request.customerName}}</span>
						<a :href="'tel:' + request.customerPhone" class="ms-2"><i class="bi bi-telephone"></i> {{request.customerPhone}}</a>
						<span class="text-muted ms-2">πριν {{minutesAgo(request.createdAt)}}′</span>
					</div>
					<span class="fw-bold fs-5">&euro;{{request.total}}</span>
				</div>
				<ul class="mb-1">
					<li v-for="entry in request.entries">
						{{entry.quantity}}× {{entry.name}}
						<span class="text-muted" v-if="entry.extras.length > 0">(+ {{entry.extras.join(', ')}})</span>
					</li>
				</ul>
				<p class="fst-italic mb-2" v-if="request.notes"><i class="bi bi-chat-left-text"></i> {{request.notes}}</p>
				<div class="btn-toolbar gap-2">
					<button class="btn btn-success" @click="acceptingRequest = request">
						<i class="bi bi-check-lg"></i> Αποδοχή
					</button>
					<button class="btn btn-outline-danger" @click="rejectingRequest = request">
						<i class="bi bi-x-lg"></i> Απόρριψη
					</button>
				</div>
			</div>
		</div>
	</div>

	<table class="table table-striped fs-4 mt-4">
		<tbody>
			<tr v-for="(order, index) in orders">
				<td>
					<a class="text-decoration-none" :href="'/take-out/update?id=' + order.id">
					<span class="fw-bold">#{{order.ticketNumber}}</span> /
					&euro;{{order.price}} /
					{{getTime(order.createdAt)}}</a>
				</td>
				<td class="text-end">
                    <button class="btn btn-lg btn-outline-secondary" @click="confirmPayment(order)">
                        <i class="bi bi-currency-euro"></i> <span class="d-none d-sm-none d-md-inline">Πληρωμή</span>
                    </button>
				</td>
			</tr>
		</tbody>
	</table>

	<div class="modal" tabindex="-1" :class="payingOrder ? 'd-block' : ''">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Επιβεβαίωση πληρωμής</h5>
					<button type="button" class="btn-close" @click="payingOrder = null"></button>
				</div>
				<div class="modal-body" v-if="payingOrder">
					<p class="fs-5">#{{payingOrder.ticketNumber}} / &euro;{{payingOrder.price}}</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary btn-lg" @click="payingOrder = null">Άκυρο</button>
					<button type="button" class="btn btn-primary btn-lg" @click="pay" :disabled="paying">
						<span class="spinner-border spinner-border-sm me-1" v-if="paying"></span>OK
					</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal" tabindex="-1" :class="acceptingRequest ? 'd-block' : ''">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Αποδοχή παραγγελίας</h5>
					<button type="button" class="btn-close" @click="acceptingRequest = null"></button>
				</div>
				<div class="modal-body" v-if="acceptingRequest">
					<p class="fs-5">{{acceptingRequest.customerName}} / &euro;{{acceptingRequest.total}}</p>
					<p>Έτοιμη σε:</p>
					<div class="btn-group mb-2">
						<button
							v-for="minutes in [15, 25, 40, 60]"
							class="btn btn-lg"
							:class="etaMinutes == minutes ? 'btn-primary' : 'btn-outline-primary'"
							@click="etaMinutes = minutes"
						>{{minutes}}′</button>
					</div>
					<input type="number" class="form-control" v-model.number="etaMinutes" min="5" max="180">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary btn-lg" @click="acceptingRequest = null">Άκυρο</button>
					<button type="button" class="btn btn-success btn-lg" @click="accept" :disabled="processingRequest">
						<span class="spinner-border spinner-border-sm me-1" v-if="processingRequest"></span>Αποδοχή
					</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal" tabindex="-1" :class="rejectingRequest ? 'd-block' : ''">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Απόρριψη παραγγελίας</h5>
					<button type="button" class="btn-close" @click="rejectingRequest = null"></button>
				</div>
				<div class="modal-body" v-if="rejectingRequest">
					<p class="fs-5">{{rejectingRequest.customerName}} / &euro;{{rejectingRequest.total}}</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary btn-lg" @click="rejectingRequest = null">Άκυρο</button>
					<button type="button" class="btn btn-danger btn-lg" @click="reject" :disabled="processingRequest">
						<span class="spinner-border spinner-border-sm me-1" v-if="processingRequest"></span>Απόρριψη
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
{% endverbatim %}{% endblock %}

{% block javascript %}
	{{ parent() }}
	<script src="/assets/js/vue.global.prod.js"></script>
	<script src="/assets/js/axios.min.js"></script>
	<script>
		vueApp = Vue.createApp({
		    data() {
		    	return {
		    		orders: [],
		    		payingOrder: null,
		    		paying: false,
		    		pendingRequests: [],
		    		acceptingRequest: null,
		    		rejectingRequest: null,
		    		etaMinutes: 25,
		    		processingRequest: false
		    	}
		    },
		    mounted() {
		    	setInterval(() => {
					this.fetchOrders()
				}, 5000)
                this.fetchOrders();

		    	setInterval(() => {
					this.fetchPendingRequests()
				}, 15000)
                this.fetchPendingRequests();
		    },
		    methods: {
		    	fetchOrders() {
		    		axios({
	                    url: '/admin/graph-ql',
	                    method: 'post',
	                    data: `{
	                        activeTakeOutOrders {
	                        	id, uuid, price, ticketNumber, createdAt
	                        }
	                    }`
	                }).then(response => {
	                    this.orders = response.data.data.activeTakeOutOrders
	                });
		    	},
		    	fetchPendingRequests() {
		    		axios.get('/take-out/requests/pending').then(response => {
		    			if (response.data.length > this.pendingRequests.length) {
		    				this.beep();
		    			}
		    			this.pendingRequests = response.data;
		    		});
		    	},
		    	beep() {
		    		const ctx = new (window.AudioContext || window.webkitAudioContext)();
		    		const oscillator = ctx.createOscillator();
		    		oscillator.type = 'sine';
		    		oscillator.frequency.value = 880;
		    		oscillator.connect(ctx.destination);
		    		oscillator.start();
		    		setTimeout(() => { oscillator.stop(); ctx.close(); }, 400);
		    	},
		    	minutesAgo(createdAt) {
		    		return Math.max(0, Math.round((Date.now() - new Date(createdAt.replace(' ', 'T'))) / 60000));
		    	},
		    	accept() {
		    		this.processingRequest = true;
		    		axios.post('/take-out/requests/' + this.acceptingRequest.id + '/accept', { etaMinutes: this.etaMinutes })
		    			.then(() => {
		    				this.acceptingRequest = null;
		    				this.fetchPendingRequests();
		    				this.fetchOrders();
		    			})
		    			.catch(error => alert(error.response?.data?.error || error))
		    			.finally(() => { this.processingRequest = false; });
		    	},
		    	reject() {
		    		this.processingRequest = true;
		    		axios.post('/take-out/requests/' + this.rejectingRequest.id + '/reject')
		    			.then(() => {
		    				this.rejectingRequest = null;
		    				this.fetchPendingRequests();
		    			})
		    			.catch(error => alert(error.response?.data?.error || error))
		    			.finally(() => { this.processingRequest = false; });
		    	},
		    	getTime(date) {
		    		return date.substr(date.length - 8, 5);
		    	},
		    	confirmPayment(order) {
		    		this.payingOrder = order;
		    	},
		    	pay() {
		    		this.paying = true;
		    		axios.post('/take-out/payment', { orderId: this.payingOrder.id })
		    			.then(response => {
		    				if (response.data === 'ok') {
		    					this.payingOrder = null;
		    					this.paying = false;
		    					this.fetchOrders();
		    				}
		    			}).catch(error => {
		    				alert(error);
		    				this.paying = false;
		    			});
		    	}
		    }
		}).mount('#app');
	</script>
{% endblock %}
```

(The original file loaded `vue.global.prod.js` and `axios.min.js` the same way; the orders table, payment modal, and their methods are unchanged — only the pending-requests section, modals, data fields, and methods are new.)

- [ ] **Step 6: Lint**

Run:
```bash
ddev exec php -l src/Application/Actions/TakeOutApp/PendingRequests.php
ddev exec php -l src/Application/Actions/TakeOutApp/AcceptRequest.php
ddev exec php -l src/Application/Actions/TakeOutApp/RejectRequest.php
ddev exec php -l app/routes.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 7: Verify the staff flow in the browser**

1. Submit a fresh customer order at `https://sop.ddev.site/order/` (or with the Task 5 curl).
2. Log in as webmaster, open `https://sop.ddev.site/take-out/` — the pending card shows name, phone link, items, total, note, and age; a beep plays when a new request arrives while the page is open.
3. Accept with ETA 25 — the card disappears, the order appears in the orders table with a ticket number, and the customer status page (open in another tab) flips to accepted with ETA + ticket number within ~10s.
4. Submit another order and reject it — customer status page flips to rejected.
5. Check the ECR job: `ddev exec 'psql -U db -d db -h db -c "SELECT id, status FROM ecr_jobs ORDER BY id DESC LIMIT 1"'` → a new `pending` row from the acceptance.

- [ ] **Step 8: Commit**

```bash
git add src/Application/Actions/TakeOutApp/PendingRequests.php src/Application/Actions/TakeOutApp/AcceptRequest.php src/Application/Actions/TakeOutApp/RejectRequest.php src/templates/take_out_app/homepage.twig app/routes.php
git commit -m "Add pending online order queue to the take-out app"
```

---

### Task 8: End-to-end verification

**Files:** none (verification only)

- [ ] **Step 1: Clean slate**

```bash
ddev exec 'psql -U db -d db -h db -c "DELETE FROM take_out_request_entry_extras; DELETE FROM take_out_request_entries; DELETE FROM take_out_requests;"'
```

- [ ] **Step 2: Walk the full spec verification list**

1. **Order with extras + note, both languages:** on a phone-sized browser window, open `/order/`, switch EL→EN, add an item with extras, set quantity 2, add an order note, submit with name + phone. Expected: redirect to status page, waiting state.
2. **Staff accept:** in another browser/profile, log in, open `/take-out/`, hear/see the pending card, accept with ETA 25. Expected: ticket number returned; customer status page shows accepted + ETA + ticket within 10s.
3. **Quantity decrement:** pick a tracked item (`track_available_quantity = true`), note its `available_quantity`, order it, accept, and confirm the quantity dropped by the ordered amount:
   `ddev exec 'psql -U db -d db -h db -c "SELECT id, available_quantity FROM menu_items WHERE track_available_quantity LIMIT 5"'`
4. **Reject path:** submit another order, reject it. Expected: customer page flips to rejected; `localStorage` token cleared on next visit; no order/ECR job created.
5. **Sold-out item:** set a tracked item to 0 (`UPDATE menu_items SET available_quantity = 0 WHERE id = …`), reload `/order/` — item shows "sold out" badge and + is disabled. Submitting a stale cart with it returns 409 with a clear message. Restore the quantity afterwards.
6. **Rate limit:** submit 3 pending orders with the same phone; the 4th returns 429.
7. **Wrong token:** `/order/status/garbage` returns 404.
8. **Staff regression:** create a staff take-out order via `/take-out/create` and pay it via the homepage — unchanged behavior (Task 3 refactor).

- [ ] **Step 3: Final commit if any fixes were needed**

```bash
git status
```
If fixes were made during verification, commit them with a message describing the fix.

---

## Self-Review Notes

- **Spec coverage:** data model (Task 1–2), public menu/cart/checkout (Task 4), submission + validation + rate limit + honeypot (Task 5), status page + polling (Task 6), staff queue + accept/ETA/reject + shared factory (Task 3, 7), edge cases + manual verification (Task 5 negatives, Task 8). Phone masking on the status page resolved by simply not rendering the phone there.
- **Spec deviations** are listed in the header and were validated against the codebase/DB, not assumed.
- **Type consistency:** `TakeOutRequestStatus` enum cases used as `TakeOutRequestStatus::Pending` etc. throughout; factory `entrySpecs` shape identical in Task 3 (both call sites) and Task 7; JSON keys (`menuItemId`, `quantity`, `extras`, `customerName`, `customerPhone`, `notes`, `website`, `etaMinutes`, `statusUrl`) consistent between templates and actions.
