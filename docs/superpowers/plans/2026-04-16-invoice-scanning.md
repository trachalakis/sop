# Invoice Scanning Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow staff to photograph supplier invoices, extract structured line-item data via Claude's vision API, persist it as Invoice/InvoiceEntry records, and progressively map invoice descriptions to Supply entities via SupplyAlias records with a price-over-time graph.

**Architecture:** A mobile-friendly scan page uploads the image to Claude, stores parsed data in the PHP session, then a review page lets the user correct OCR output and resolve the supplier before saving. Confirmed invoices auto-link to existing SupplyAlias records; unlinked entries can be mapped inline on the invoice detail page, which backfills all historical matching entries.

**Tech Stack:** Slim 4, Doctrine ORM 3, Twig 3, Bootstrap 5, Chart.js 4, PHP sessions, Claude API (`claude-sonnet-4-6`), PostgreSQL, DDEV

---

## Codebase context (read before implementing)

- All routes are registered in `app/routes.php` inside the `/admin` group.
- DI bindings live in `app/dependencies.php`.
- Action classes live in `src/Application/Actions/Admin/`, one class per route, single `__invoke(Request, Response): Response`.
- Doctrine entities live in `src/Domain/Entities/` with PHP 8 attribute mapping.
- Repositories extend `EntityRepository` and live in `src/Domain/Repositories/`. They expose `persist()` and `delete()` helpers that call `->flush()`.
- Twig templates live in `src/templates/admin/`. They extend `admin/skeleton.twig` and set `{% set activeNavLink = '...' %}`.
- User-visible strings use the `|_` Twig filter (`{{ "Αποθήκευση"|_ }}`).
- The admin skeleton already has a `{% block pageCss %}{% endblock %}` in `<head>` and a `{% block javascript %}{% endblock %}` near `</body>`.
- `confirm` CSS class on an `<a>` tag triggers a JS confirm dialog (already wired in the skeleton).
- There is **no test suite**. Verification is done by loading the page in a browser at `https://sop.ddev.site`.

---

## File map

**New files:**
- `migrations/create_invoices_and_aliases.php` — creates `invoices`, `invoice_entries`, `supply_aliases`
- `src/Domain/Entities/Invoice.php`
- `src/Domain/Entities/InvoiceEntry.php`
- `src/Domain/Entities/SupplyAlias.php`
- `src/Domain/Repositories/InvoicesRepository.php`
- `src/Domain/Repositories/InvoiceEntriesRepository.php`
- `src/Domain/Repositories/SupplyAliasesRepository.php`
- `src/Application/Services/InvoiceParserService.php`
- `src/Application/Actions/Admin/ScanInvoice.php`
- `src/Application/Actions/Admin/ReviewInvoice.php`
- `src/Application/Actions/Admin/ConfirmInvoice.php`
- `src/Application/Actions/Admin/Invoices.php`
- `src/Application/Actions/Admin/ViewInvoice.php`
- `src/Application/Actions/Admin/LinkInvoiceEntry.php`
- `src/Application/Actions/Admin/SupplyAliases.php`
- `src/Application/Actions/Admin/DeleteSupplyAlias.php`
- `src/templates/admin/scan_invoice.twig`
- `src/templates/admin/review_invoice.twig`
- `src/templates/admin/invoices.twig`
- `src/templates/admin/view_invoice.twig`
- `src/templates/admin/supply_aliases.twig`

**Modified files:**
- `app/.env.example` — add `ANTHROPIC_API_KEY`
- `app/dependencies.php` — register 3 repositories + `InvoiceParserService`
- `app/routes.php` — add 8 use statements + 8 route registrations
- `src/templates/admin/skeleton.twig` — add "Τιμολόγια" and "Ψευδώνυμα προμηθειών" nav entries

---

## Task 1: Database migration

**Files:**
- Create: `migrations/create_invoices_and_aliases.php`

- [ ] **Step 1: Write the migration file**

```php
<?php

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

echo "Connected to '{$_ENV['DB_NAME']}'.\n";

// Idempotency guard
$exists = $pdo->query("
    SELECT table_name FROM information_schema.tables
    WHERE table_name = 'invoices' AND table_schema NOT IN ('pg_catalog','information_schema')
")->fetchColumn();
if ($exists) {
    die("Table 'invoices' already exists. Migration may have already run.\n");
}

$pdo->exec("
    CREATE TABLE supply_aliases (
        id          SERIAL PRIMARY KEY,
        supply_id   INTEGER NOT NULL REFERENCES supplies(id) ON DELETE CASCADE,
        supplier_id INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE CASCADE,
        description VARCHAR(500) NOT NULL,
        UNIQUE (supplier_id, description)
    );

    CREATE TABLE invoices (
        id             SERIAL PRIMARY KEY,
        supplier_id    INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
        date           DATE NOT NULL,
        invoice_number VARCHAR(100),
        scanned_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );

    CREATE TABLE invoice_entries (
        id              SERIAL PRIMARY KEY,
        invoice_id      INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
        description     VARCHAR(500) NOT NULL,
        quantity        FLOAT NOT NULL,
        unit_price      FLOAT NOT NULL,
        extras          JSONB,
        supply_alias_id INTEGER REFERENCES supply_aliases(id) ON DELETE SET NULL
    );
");

echo "Done: created supply_aliases, invoices, invoice_entries.\n";
```

- [ ] **Step 2: Run the migration**

```bash
ddev exec php migrations/create_invoices_and_aliases.php
```

Expected output:
```
Connected to 'db'.
Done: created supply_aliases, invoices, invoice_entries.
```

- [ ] **Step 3: Commit**

```bash
git add migrations/create_invoices_and_aliases.php
git commit -m "feat: migrate — create invoices, invoice_entries, supply_aliases tables"
```

---

## Task 2: Entities

**Files:**
- Create: `src/Domain/Entities/Invoice.php`
- Create: `src/Domain/Entities/InvoiceEntry.php`
- Create: `src/Domain/Entities/SupplyAlias.php`

- [ ] **Step 1: Create `SupplyAlias` entity**

`src/Domain/Entities/SupplyAlias.php`:

```php
<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\SupplyAliasesRepository;

#[ORM\Entity(repositoryClass: SupplyAliasesRepository::class)]
#[ORM\Table(name: 'supply_aliases')]
#[ORM\UniqueConstraint(columns: ['supplier_id', 'description'])]
class SupplyAlias
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Supply::class)]
    #[ORM\JoinColumn(name: 'supply_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Supply $supply;

    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(name: 'supplier_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Supplier $supplier;

    #[ORM\Column(type: 'string', length: 500)]
    private string $description;

    public function getId(): int { return $this->id; }
    public function getSupply(): Supply { return $this->supply; }
    public function getSupplier(): Supplier { return $this->supplier; }
    public function getDescription(): string { return $this->description; }

    public function setSupply(Supply $supply): void { $this->supply = $supply; }
    public function setSupplier(Supplier $supplier): void { $this->supplier = $supplier; }
    public function setDescription(string $description): void { $this->description = $description; }
}
```

- [ ] **Step 2: Create `Invoice` entity**

`src/Domain/Entities/Invoice.php`:

```php
<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\InvoicesRepository;

#[ORM\Entity(repositoryClass: InvoicesRepository::class)]
#[ORM\Table(name: 'invoices')]
class Invoice
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(name: 'supplier_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Supplier $supplier;

    #[ORM\Column(type: 'date')]
    private DateTimeInterface $date;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(type: 'datetimetz')]
    private DateTimeInterface $scannedAt;

    #[ORM\OneToMany(targetEntity: InvoiceEntry::class, mappedBy: 'invoice', cascade: ['persist'])]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }
    public function getSupplier(): Supplier { return $this->supplier; }
    public function getDate(): DateTimeInterface { return $this->date; }
    public function getInvoiceNumber(): ?string { return $this->invoiceNumber; }
    public function getScannedAt(): DateTimeInterface { return $this->scannedAt; }
    public function getEntries(): Collection { return $this->entries; }

    public function setSupplier(Supplier $supplier): void { $this->supplier = $supplier; }
    public function setDate(DateTimeInterface $date): void { $this->date = $date; }
    public function setInvoiceNumber(?string $invoiceNumber): void { $this->invoiceNumber = $invoiceNumber; }
    public function setScannedAt(DateTimeInterface $scannedAt): void { $this->scannedAt = $scannedAt; }

    public function addEntry(InvoiceEntry $entry): void
    {
        $this->entries->add($entry);
        $entry->setInvoice($this);
    }
}
```

- [ ] **Step 3: Create `InvoiceEntry` entity**

`src/Domain/Entities/InvoiceEntry.php`:

```php
<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\InvoiceEntriesRepository;

#[ORM\Entity(repositoryClass: InvoiceEntriesRepository::class)]
#[ORM\Table(name: 'invoice_entries')]
class InvoiceEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Invoice $invoice;

    #[ORM\Column(type: 'string', length: 500)]
    private string $description;

    #[ORM\Column(type: 'float')]
    private float $quantity;

    #[ORM\Column(type: 'float', name: 'unit_price')]
    private float $unitPrice;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $extras = null;

    #[ORM\ManyToOne(targetEntity: SupplyAlias::class)]
    #[ORM\JoinColumn(name: 'supply_alias_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?SupplyAlias $supplyAlias = null;

    public function getId(): int { return $this->id; }
    public function getInvoice(): Invoice { return $this->invoice; }
    public function getDescription(): string { return $this->description; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getExtras(): ?array { return $this->extras; }
    public function getSupplyAlias(): ?SupplyAlias { return $this->supplyAlias; }

    public function setInvoice(Invoice $invoice): void { $this->invoice = $invoice; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setQuantity(float $quantity): void { $this->quantity = $quantity; }
    public function setUnitPrice(float $unitPrice): void { $this->unitPrice = $unitPrice; }
    public function setExtras(?array $extras): void { $this->extras = $extras; }
    public function setSupplyAlias(?SupplyAlias $supplyAlias): void { $this->supplyAlias = $supplyAlias; }
}
```

- [ ] **Step 4: Commit**

```bash
git add src/Domain/Entities/Invoice.php src/Domain/Entities/InvoiceEntry.php src/Domain/Entities/SupplyAlias.php
git commit -m "feat: add Invoice, InvoiceEntry, SupplyAlias Doctrine entities"
```

---

## Task 3: Repositories

**Files:**
- Create: `src/Domain/Repositories/InvoicesRepository.php`
- Create: `src/Domain/Repositories/InvoiceEntriesRepository.php`
- Create: `src/Domain/Repositories/SupplyAliasesRepository.php`

- [ ] **Step 1: Create `InvoicesRepository`**

`src/Domain/Repositories/InvoicesRepository.php`:

```php
<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Doctrine\ORM\EntityRepository;
use Domain\Entities\Invoice;

class InvoicesRepository extends EntityRepository
{
    public function persist(Invoice $invoice): void
    {
        $this->getEntityManager()->persist($invoice);
        $this->getEntityManager()->flush();
    }
}
```

- [ ] **Step 2: Create `InvoiceEntriesRepository`**

`src/Domain/Repositories/InvoiceEntriesRepository.php`:

```php
<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Doctrine\ORM\EntityRepository;
use Domain\Entities\InvoiceEntry;
use Domain\Entities\SupplyAlias;

class InvoiceEntriesRepository extends EntityRepository
{
    /**
     * Find all entries for a given supplier + description combination and set their supply alias.
     * Used when the user maps an invoice description to a Supply for the first time.
     */
    public function linkAllBySupplierAndDescription(int $supplierId, string $description, SupplyAlias $alias): void
    {
        $entries = $this->getEntityManager()->createQuery(
            'SELECT e FROM Domain\Entities\InvoiceEntry e
             JOIN e.invoice i
             WHERE e.description = :desc AND i.supplier = :supplierId'
        )
        ->setParameter('desc', $description)
        ->setParameter('supplierId', $supplierId)
        ->getResult();

        foreach ($entries as $entry) {
            $entry->setSupplyAlias($alias);
        }
        $this->getEntityManager()->flush();
    }
}
```

- [ ] **Step 3: Create `SupplyAliasesRepository`**

`src/Domain/Repositories/SupplyAliasesRepository.php`:

```php
<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Doctrine\ORM\EntityRepository;
use Domain\Entities\SupplyAlias;

class SupplyAliasesRepository extends EntityRepository
{
    public function persist(SupplyAlias $alias): void
    {
        $this->getEntityManager()->persist($alias);
        $this->getEntityManager()->flush();
    }

    public function delete(SupplyAlias $alias): void
    {
        $this->getEntityManager()->remove($alias);
        $this->getEntityManager()->flush();
    }

    /**
     * Find a specific alias by supplier + description, or null if not yet mapped.
     */
    public function findBySupplierAndDescription(int $supplierId, string $description): ?SupplyAlias
    {
        return $this->findOneBy([
            'supplier' => $supplierId,
            'description' => $description,
        ]);
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add src/Domain/Repositories/InvoicesRepository.php src/Domain/Repositories/InvoiceEntriesRepository.php src/Domain/Repositories/SupplyAliasesRepository.php
git commit -m "feat: add InvoicesRepository, InvoiceEntriesRepository, SupplyAliasesRepository"
```

---

## Task 4: DI registration and API key config

**Files:**
- Modify: `app/.env.example`
- Modify: `app/dependencies.php`

- [ ] **Step 1: Add `ANTHROPIC_API_KEY` to `.env.example`**

Open `app/.env.example` and append:

```
ANTHROPIC_API_KEY=""
```

Then add the real key to your local `app/.env`.

- [ ] **Step 2: Register repositories and service in `app/dependencies.php`**

At the top of `app/dependencies.php`, add these `use` statements alongside the existing ones:

```php
use Domain\Entities\Invoice;
use Domain\Entities\InvoiceEntry;
use Domain\Entities\SupplyAlias;
use Domain\Repositories\InvoicesRepository;
use Domain\Repositories\InvoiceEntriesRepository;
use Domain\Repositories\SupplyAliasesRepository;
use Application\Services\InvoiceParserService;
```

Inside the `$containerBuilder->addDefinitions([...])` array, add:

```php
InvoicesRepository::class => function (ContainerInterface $c) {
    return $c->get(EntityManager::class)->getRepository(Invoice::class);
},
InvoiceEntriesRepository::class => function (ContainerInterface $c) {
    return $c->get(EntityManager::class)->getRepository(InvoiceEntry::class);
},
SupplyAliasesRepository::class => function (ContainerInterface $c) {
    return $c->get(EntityManager::class)->getRepository(SupplyAlias::class);
},
InvoiceParserService::class => function (ContainerInterface $c) {
    return new InvoiceParserService($_ENV['ANTHROPIC_API_KEY']);
},
```

- [ ] **Step 3: Verify the app still loads**

Visit `https://sop.ddev.site/admin/` — should load without errors.

- [ ] **Step 4: Commit**

```bash
git add app/.env.example app/dependencies.php
git commit -m "feat: register invoice repositories and InvoiceParserService in DI"
```

---

## Task 5: InvoiceParserService

**Files:**
- Create: `src/Application/Services/InvoiceParserService.php`

- [ ] **Step 1: Create the service**

`src/Application/Services/InvoiceParserService.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Services;

use RuntimeException;

/**
 * Sends an invoice image to the Claude vision API and returns structured data.
 *
 * Returns an array with keys:
 *   supplier_name    string
 *   supplier_details array{afm:?string, doy:?string, address:?string, email:?string, website:?string}
 *   invoice_number   string|null
 *   date             string|null  (YYYY-MM-DD)
 *   entries          array of {description:string, quantity:float, unit_price:float, extras:array}
 */
final class InvoiceParserService
{
    private const MODEL = 'claude-sonnet-4-6';
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    public function __construct(private string $apiKey) {}

    /**
     * @param string $imageData  Raw binary image bytes
     * @param string $mediaType  E.g. 'image/jpeg', 'image/png'
     * @return array             Parsed invoice data
     * @throws RuntimeException  On API or parse error
     */
    public function parse(string $imageData, string $mediaType): array
    {
        $base64 = base64_encode($imageData);

        $payload = [
            'model' => self::MODEL,
            'max_tokens' => 2048,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mediaType,
                                'data' => $base64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Extract all invoice data from this image and return ONLY a valid JSON object with this exact structure (no markdown, no extra text):
{
  "supplier_name": "string as it appears on the invoice",
  "supplier_details": {
    "afm": "VAT number / ΑΦΜ or null",
    "doy": "ΔΟΥ or null",
    "address": "full address or null",
    "email": "email or null",
    "website": "website or null"
  },
  "invoice_number": "string or null",
  "date": "YYYY-MM-DD or null",
  "entries": [
    {
      "description": "exact description as on invoice",
      "quantity": 1.0,
      "unit_price": 0.0,
      "extras": {
        "unit": "string or null",
        "vat_rate": 0,
        "line_total": 0.0
      }
    }
  ]
}',
                        ],
                    ],
                ],
            ],
        ];

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException('Claude API curl error: ' . $curlError);
        }
        if ($httpCode !== 200) {
            throw new RuntimeException('Claude API returned HTTP ' . $httpCode . ': ' . $responseBody);
        }

        $response = json_decode($responseBody, true);
        $text = $response['content'][0]['text'] ?? '';

        // Strip markdown code fences if present
        $text = preg_replace('/^```json\s*|\s*```$/s', '', trim($text));

        $data = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Could not parse Claude response as JSON: ' . $text);
        }

        return $data;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Application/Services/InvoiceParserService.php
git commit -m "feat: add InvoiceParserService for Claude vision API invoice parsing"
```

---

## Task 6: Routes registration

**Files:**
- Modify: `app/routes.php`

- [ ] **Step 1: Add `use` statements**

In `app/routes.php`, alongside the existing `use` statements for Admin actions, add:

```php
use Application\Actions\Admin\ScanInvoice;
use Application\Actions\Admin\ReviewInvoice;
use Application\Actions\Admin\ConfirmInvoice;
use Application\Actions\Admin\Invoices;
use Application\Actions\Admin\ViewInvoice;
use Application\Actions\Admin\LinkInvoiceEntry;
use Application\Actions\Admin\SupplyAliases;
use Application\Actions\Admin\DeleteSupplyAlias;
```

- [ ] **Step 2: Register routes inside the `/admin` group**

Inside the `$app->group('/admin', ...)` callback, alongside the suppliers routes, add:

```php
$group->get('/invoices', Invoices::class);
$group->map(['GET', 'POST'], '/invoices/scan', ScanInvoice::class);
$group->get('/invoices/review', ReviewInvoice::class);
$group->post('/invoices/confirm', ConfirmInvoice::class);
$group->get('/invoices/view', ViewInvoice::class);
$group->post('/invoices/link-entry', LinkInvoiceEntry::class);

$group->get('/supply-aliases', SupplyAliases::class);
$group->get('/supply-aliases/delete', DeleteSupplyAlias::class);
```

- [ ] **Step 3: Commit**

```bash
git add app/routes.php
git commit -m "feat: register invoice scanning and supply alias routes"
```

---

## Task 7: ScanInvoice action + template

**Files:**
- Create: `src/Application/Actions/Admin/ScanInvoice.php`
- Create: `src/templates/admin/scan_invoice.twig`

The GET renders a minimal mobile-friendly upload form. The POST uploads the image to Claude, stores parsed data in `$_SESSION['invoice_scan']`, then redirects to the review page.

- [ ] **Step 1: Create the action**

`src/Application/Actions/Admin/ScanInvoice.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Application\Services\InvoiceParserService;
use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Slim\Views\Twig;

final class ScanInvoice
{
    public function __construct(
        private InvoiceParserService $parser,
        private SuppliersRepository $suppliersRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        if ($request->getMethod() === 'POST') {
            $files = $request->getUploadedFiles();

            /** @var UploadedFileInterface|null $file */
            $file = $files['invoice'] ?? null;

            if ($file === null || $file->getError() !== UPLOAD_ERR_OK) {
                return $this->twig->render($response, 'admin/scan_invoice.twig', [
                    'error' => 'Δεν ανέβηκε αρχείο ή παρουσιάστηκε σφάλμα.',
                ]);
            }

            $mediaType = $file->getClientMediaType() ?: 'image/jpeg';
            $imageData = (string) $file->getStream();

            try {
                $parsed = $this->parser->parse($imageData, $mediaType);
            } catch (RuntimeException $e) {
                return $this->twig->render($response, 'admin/scan_invoice.twig', [
                    'error' => 'Σφάλμα ανάλυσης τιμολογίου: ' . $e->getMessage(),
                ]);
            }

            // Attempt case-insensitive supplier match
            $allSuppliers = $this->suppliersRepository->findAll();
            $matchedSupplierId = null;
            foreach ($allSuppliers as $supplier) {
                if (mb_strtolower($supplier->getName()) === mb_strtolower($parsed['supplier_name'] ?? '')) {
                    $matchedSupplierId = $supplier->getId();
                    break;
                }
            }

            $_SESSION['invoice_scan'] = [
                'supplier_name'    => $parsed['supplier_name'] ?? '',
                'supplier_details' => $parsed['supplier_details'] ?? [],
                'supplier_id'      => $matchedSupplierId,
                'invoice_number'   => $parsed['invoice_number'] ?? null,
                'date'             => $parsed['date'] ?? null,
                'entries'          => $parsed['entries'] ?? [],
            ];

            return $response->withHeader('Location', '/admin/invoices/review')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/scan_invoice.twig', []);
    }
}
```

- [ ] **Step 2: Create the template**

`src/templates/admin/scan_invoice.twig`:

```twig
{% extends 'admin/skeleton.twig' %}

{% set activeNavLink = 'invoices' %}

{% block content %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/admin/" class="text-decoration-none"><i class="bi bi-house"></i></a>
    </li>
    <li class="breadcrumb-item">
        <a href="/admin/invoices" class="text-decoration-none">Τιμολόγια</a>
    </li>
    <li class="breadcrumb-item active">Σάρωση</li>
</ol>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-1 mb-3 border-bottom">
    <h1 class="h2">{{'Σάρωση τιμολογίου'|_}}</h1>
</div>

{% if error is defined %}
    <div class="alert alert-danger">{{error}}</div>
{% endif %}

<form method="post" enctype="multipart/form-data" class="mt-3" style="max-width:480px">
    <div class="mb-3">
        <label class="form-label fw-semibold">{{'Φωτογραφία τιμολογίου'|_}}</label>
        <input
            type="file"
            name="invoice"
            class="form-control form-control-lg"
            accept="image/*"
            capture="environment"
            required
        >
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-100">{{'Ανάλυση'|_}}</button>
</form>
{% endblock %}
```

- [ ] **Step 3: Verify**

Navigate to `https://sop.ddev.site/admin/invoices/scan` — the form should render with a camera-capture file input.

- [ ] **Step 4: Commit**

```bash
git add src/Application/Actions/Admin/ScanInvoice.php src/templates/admin/scan_invoice.twig
git commit -m "feat: add ScanInvoice action and template"
```

---

## Task 8: ReviewInvoice action + template

**Files:**
- Create: `src/Application/Actions/Admin/ReviewInvoice.php`
- Create: `src/templates/admin/review_invoice.twig`

The review page reads from `$_SESSION['invoice_scan']`. If the supplier was matched, it shows the name read-only. If not, it shows a warning with a supplier dropdown AND a "create new" section.

- [ ] **Step 1: Create the action**

`src/Application/Actions/Admin/ReviewInvoice.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class ReviewInvoice
{
    public function __construct(
        private SuppliersRepository $suppliersRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $scan = $_SESSION['invoice_scan'] ?? null;

        if ($scan === null) {
            return $response->withHeader('Location', '/admin/invoices/scan')->withStatus(302);
        }

        $suppliers = $this->suppliersRepository->findBy([], ['name' => 'ASC']);

        return $this->twig->render($response, 'admin/review_invoice.twig', [
            'scan'      => $scan,
            'suppliers' => $suppliers,
        ]);
    }
}
```

- [ ] **Step 2: Create the template**

`src/templates/admin/review_invoice.twig`:

```twig
{% extends 'admin/skeleton.twig' %}

{% set activeNavLink = 'invoices' %}

{% block content %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/admin/" class="text-decoration-none"><i class="bi bi-house"></i></a>
    </li>
    <li class="breadcrumb-item">
        <a href="/admin/invoices" class="text-decoration-none">Τιμολόγια</a>
    </li>
    <li class="breadcrumb-item active">Επισκόπηση</li>
</ol>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-1 mb-3 border-bottom">
    <h1 class="h2">{{'Επισκόπηση τιμολογίου'|_}}</h1>
</div>

<form method="post" action="/admin/invoices/confirm">

    {# Supplier section #}
    <div class="mb-3">
        <label class="form-label fw-semibold">{{'Προμηθευτής'|_}}</label>
        {% if scan.supplier_id %}
            <p class="form-control-plaintext">
                {% for supplier in suppliers %}
                    {% if supplier.getId == scan.supplier_id %}{{supplier.getName}}{% endif %}
                {% endfor %}
            </p>
            <input type="hidden" name="supplier_id" value="{{scan.supplier_id}}">
        {% else %}
            <div class="alert alert-warning">
                {{'Προμηθευτής'|_}} «{{scan.supplier_name}}» {{'δεν βρέθηκε'|_}}.
            </div>
            <div class="mb-2">
                <label class="form-label">{{'Επιλογή υπάρχοντος'|_}}</label>
                <select name="supplier_id" class="form-select">
                    <option value="">{{'— Επιλέξτε —'|_}}</option>
                    {% for supplier in suppliers %}
                        <option value="{{supplier.getId}}">{{supplier.getName}}</option>
                    {% endfor %}
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">{{'ή Δημιουργία νέου'|_}}</label>
                <div class="row g-2">
                    <div class="col">
                        <input type="text" name="new_supplier_name" class="form-control" placeholder="{{'Όνομα'|_}}">
                    </div>
                    <div class="col">
                        <input type="text" name="new_supplier_telephone" class="form-control" placeholder="{{'Τηλέφωνο'|_}}">
                    </div>
                </div>
            </div>
        {% endif %}
    </div>

    {# Invoice header #}
    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label fw-semibold">{{'Ημερομηνία'|_}}</label>
            <input type="date" name="date" class="form-control" value="{{scan.date}}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">{{'Αριθμός τιμολογίου'|_}}</label>
            <input type="text" name="invoice_number" class="form-control" value="{{scan.invoice_number}}">
        </div>
    </div>

    {# Line items #}
    <h5 class="mb-2">{{'Γραμμές'|_}}</h5>
    <table class="table table-sm">
        <thead>
            <tr>
                <th>{{'Περιγραφή'|_}}</th>
                <th>{{'Ποσότητα'|_}}</th>
                <th>{{'Τιμή μονάδας'|_}}</th>
            </tr>
        </thead>
        <tbody>
            {% for i, entry in scan.entries %}
            <tr>
                <td>
                    <input type="text" name="entries[{{i}}][description]" class="form-control form-control-sm" value="{{entry.description}}" required>
                </td>
                <td>
                    <input type="number" step="0.001" name="entries[{{i}}][quantity]" class="form-control form-control-sm" value="{{entry.quantity}}" required>
                </td>
                <td>
                    <input type="number" step="0.001" name="entries[{{i}}][unit_price]" class="form-control form-control-sm" value="{{entry.unit_price}}" required>
                </td>
                {# Preserve extras as hidden fields #}
                {% if entry.extras is defined %}
                    <input type="hidden" name="entries[{{i}}][extras]" value='{{entry.extras|json_encode}}'>
                {% endif %}
            </tr>
            {% endfor %}
        </tbody>
    </table>

    <button type="submit" class="btn btn-primary">{{'Αποθήκευση'|_}}</button>
</form>
{% endblock %}
```

- [ ] **Step 3: Verify**

After scanning an invoice (or manually setting `$_SESSION['invoice_scan']` in a test script), navigate to `https://sop.ddev.site/admin/invoices/review`. The form should show the parsed data.

- [ ] **Step 4: Commit**

```bash
git add src/Application/Actions/Admin/ReviewInvoice.php src/templates/admin/review_invoice.twig
git commit -m "feat: add ReviewInvoice action and template"
```

---

## Task 9: ConfirmInvoice action

**Files:**
- Create: `src/Application/Actions/Admin/ConfirmInvoice.php`

This action saves the Invoice, InvoiceEntry records, auto-links matching SupplyAlias records, backfills `Supplier.details` if empty, then clears the session.

- [ ] **Step 1: Create the action**

`src/Application/Actions/Admin/ConfirmInvoice.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTime;
use Domain\Entities\Invoice;
use Domain\Entities\InvoiceEntry;
use Domain\Entities\Supplier;
use Domain\Repositories\InvoicesRepository;
use Domain\Repositories\SupplyAliasesRepository;
use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ConfirmInvoice
{
    public function __construct(
        private InvoicesRepository $invoicesRepository,
        private SupplyAliasesRepository $supplyAliasesRepository,
        private SuppliersRepository $suppliersRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $scan = $_SESSION['invoice_scan'] ?? null;
        if ($scan === null) {
            return $response->withHeader('Location', '/admin/invoices/scan')->withStatus(302);
        }

        $data = $request->getParsedBody();

        // Resolve supplier
        $supplier = null;
        if (!empty($data['new_supplier_name'])) {
            // Create new supplier inline
            $supplier = new Supplier();
            $supplier->setName(trim($data['new_supplier_name']));
            $supplier->setTelephone(!empty($data['new_supplier_telephone']) ? trim($data['new_supplier_telephone']) : null);
            $supplier->setDetails(!empty($scan['supplier_details']) ? $scan['supplier_details'] : null);
            $this->suppliersRepository->persist($supplier);
        } else {
            $supplier = $this->suppliersRepository->find((int) $data['supplier_id']);
        }

        if ($supplier === null) {
            // Fall back to review with error — redirect back
            return $response->withHeader('Location', '/admin/invoices/review')->withStatus(302);
        }

        // Backfill supplier details if empty
        if ($supplier->getDetails() === null && !empty($scan['supplier_details'])) {
            $supplier->setDetails($scan['supplier_details']);
            $this->suppliersRepository->persist($supplier);
        }

        // Build Invoice
        $invoice = new Invoice();
        $invoice->setSupplier($supplier);
        $invoice->setDate(new DateTime($data['date']));
        $invoice->setInvoiceNumber(!empty($data['invoice_number']) ? $data['invoice_number'] : null);
        $invoice->setScannedAt(new \DateTimeImmutable());

        // Build entries
        foreach ($data['entries'] as $entryData) {
            $entry = new InvoiceEntry();
            $entry->setDescription($entryData['description']);
            $entry->setQuantity((float) $entryData['quantity']);
            $entry->setUnitPrice((float) $entryData['unit_price']);
            $extras = isset($entryData['extras']) ? json_decode($entryData['extras'], true) : null;
            $entry->setExtras($extras ?: null);

            // Auto-link supply alias if one exists for this supplier + description
            $alias = $this->supplyAliasesRepository->findBySupplierAndDescription(
                $supplier->getId(),
                $entryData['description']
            );
            $entry->setSupplyAlias($alias);

            $invoice->addEntry($entry);
        }

        $this->invoicesRepository->persist($invoice);

        unset($_SESSION['invoice_scan']);

        return $response->withHeader('Location', '/admin/invoices/view?id=' . $invoice->getId())->withStatus(302);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Application/Actions/Admin/ConfirmInvoice.php
git commit -m "feat: add ConfirmInvoice action — saves invoice, auto-links aliases, backfills supplier details"
```

---

## Task 10: Invoice list

**Files:**
- Create: `src/Application/Actions/Admin/Invoices.php`
- Create: `src/templates/admin/invoices.twig`

- [ ] **Step 1: Create the action**

`src/Application/Actions/Admin/Invoices.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\InvoicesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Invoices
{
    public function __construct(
        private InvoicesRepository $invoicesRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $invoices = $this->invoicesRepository->findBy([], ['scannedAt' => 'DESC']);

        return $this->twig->render($response, 'admin/invoices.twig', [
            'invoices' => $invoices,
        ]);
    }
}
```

- [ ] **Step 2: Create the template**

`src/templates/admin/invoices.twig`:

```twig
{% extends 'admin/skeleton.twig' %}

{% set activeNavLink = 'invoices' %}

{% block content %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/admin/" class="text-decoration-none"><i class="bi bi-house"></i></a>
    </li>
    <li class="breadcrumb-item active">Τιμολόγια</li>
</ol>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-1 mb-3 border-bottom">
    <h1 class="h2">{{'Τιμολόγια'|_}}</h1>
    <div class="btn-toolbar">
        <a href="/admin/invoices/scan" class="btn btn-primary">
            <i class="bi bi-camera"></i>
        </a>
    </div>
</div>

<table class="table">
    <thead>
        <tr>
            <th>{{'Ημερομηνία'|_}}</th>
            <th>{{'Προμηθευτής'|_}}</th>
            <th>{{'Αριθμός'|_}}</th>
            <th>{{'Γραμμές'|_}}</th>
        </tr>
    </thead>
    <tbody>
        {% for invoice in invoices %}
        <tr>
            <td>
                <a class="text-decoration-none" href="/admin/invoices/view?id={{invoice.getId}}">
                    {{invoice.getDate.format('d/m/Y')}}
                </a>
            </td>
            <td>{{invoice.getSupplier.getName}}</td>
            <td>{{invoice.getInvoiceNumber}}</td>
            <td>{{invoice.getEntries|length}}</td>
        </tr>
        {% endfor %}
    </tbody>
</table>
{% endblock %}
```

- [ ] **Step 3: Verify**

Navigate to `https://sop.ddev.site/admin/invoices` — table should render (empty at first).

- [ ] **Step 4: Commit**

```bash
git add src/Application/Actions/Admin/Invoices.php src/templates/admin/invoices.twig
git commit -m "feat: add Invoices list action and template"
```

---

## Task 11: Invoice detail

**Files:**
- Create: `src/Application/Actions/Admin/ViewInvoice.php`
- Create: `src/templates/admin/view_invoice.twig`

The detail page shows the invoice header and a line items table. Linked entries show the supply name; unlinked entries show a "Σύνδεση" button that reveals an inline supply dropdown form.

- [ ] **Step 1: Create the action**

`src/Application/Actions/Admin/ViewInvoice.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\InvoicesRepository;
use Domain\Repositories\SuppliesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class ViewInvoice
{
    public function __construct(
        private InvoicesRepository $invoicesRepository,
        private SuppliesRepository $suppliesRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $invoice = $this->invoicesRepository->find((int) ($params['id'] ?? 0));

        if ($invoice === null) {
            return $response->withStatus(404);
        }

        $supplies = $this->suppliesRepository->findBy([], ['name' => 'ASC']);

        return $this->twig->render($response, 'admin/view_invoice.twig', [
            'invoice'  => $invoice,
            'supplies' => $supplies,
        ]);
    }
}
```

- [ ] **Step 2: Create the template**

`src/templates/admin/view_invoice.twig`:

```twig
{% extends 'admin/skeleton.twig' %}

{% set activeNavLink = 'invoices' %}

{% block content %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/admin/" class="text-decoration-none"><i class="bi bi-house"></i></a>
    </li>
    <li class="breadcrumb-item">
        <a href="/admin/invoices" class="text-decoration-none">Τιμολόγια</a>
    </li>
    <li class="breadcrumb-item active">{{invoice.getDate.format('d/m/Y')}}</li>
</ol>

<div class="pb-1 mb-3 border-bottom">
    <h1 class="h2">{{invoice.getSupplier.getName}}</h1>
    <p class="text-muted mb-0">
        {{invoice.getDate.format('d/m/Y')}}
        {% if invoice.getInvoiceNumber %}· {{invoice.getInvoiceNumber}}{% endif %}
        · {{'Σαρώθηκε'|_}} {{invoice.getScannedAt.format('d/m/Y H:i')}}
    </p>
</div>

<table class="table">
    <thead>
        <tr>
            <th>{{'Περιγραφή'|_}}</th>
            <th>{{'Ποσότητα'|_}}</th>
            <th>{{'Τιμή μον.'|_}}</th>
            <th>{{'Προμήθεια'|_}}</th>
        </tr>
    </thead>
    <tbody>
        {% for entry in invoice.getEntries %}
        <tr>
            <td>{{entry.getDescription}}</td>
            <td>{{entry.getQuantity}}</td>
            <td>{{entry.getUnitPrice}}</td>
            <td>
                {% if entry.getSupplyAlias %}
                    <a href="/admin/supplies/update?id={{entry.getSupplyAlias.getSupply.getId}}" class="text-decoration-none">
                        {{entry.getSupplyAlias.getSupply.getName}}
                    </a>
                {% else %}
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="collapse"
                        data-bs-target="#link-{{entry.getId}}"
                    >
                        {{'Σύνδεση'|_}}
                    </button>
                    <div class="collapse mt-2" id="link-{{entry.getId}}">
                        <form method="post" action="/admin/invoices/link-entry" class="d-flex gap-2">
                            <input type="hidden" name="invoice_entry_id" value="{{entry.getId}}">
                            <select name="supply_id" class="form-select form-select-sm">
                                <option value="">{{'Επιλέξτε'|_}}</option>
                                {% for supply in supplies %}
                                    <option value="{{supply.getId}}">{{supply.getName}}</option>
                                {% endfor %}
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">{{'Αποθήκευση'|_}}</button>
                        </form>
                    </div>
                {% endif %}
            </td>
        </tr>
        {% endfor %}
    </tbody>
</table>
{% endblock %}
```

- [ ] **Step 3: Verify**

After saving an invoice through the scan flow, the detail page should show all line items. Each unlinked entry should have a "Σύνδεση" button that reveals a supply dropdown.

- [ ] **Step 4: Commit**

```bash
git add src/Application/Actions/Admin/ViewInvoice.php src/templates/admin/view_invoice.twig
git commit -m "feat: add ViewInvoice detail action and template"
```

---

## Task 12: LinkInvoiceEntry action

**Files:**
- Create: `src/Application/Actions/Admin/LinkInvoiceEntry.php`

This action:
1. Finds the InvoiceEntry
2. Creates a SupplyAlias (supplier+supply+description) if it does not already exist
3. Updates **all** InvoiceEntry rows for that supplier+description pair to set `supply_alias_id`
4. Redirects back to the invoice detail page

- [ ] **Step 1: Create the action**

`src/Application/Actions/Admin/LinkInvoiceEntry.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\SupplyAlias;
use Domain\Repositories\InvoiceEntriesRepository;
use Domain\Repositories\SupplyAliasesRepository;
use Domain\Repositories\SuppliesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LinkInvoiceEntry
{
    public function __construct(
        private InvoiceEntriesRepository $invoiceEntriesRepository,
        private SupplyAliasesRepository $supplyAliasesRepository,
        private SuppliesRepository $suppliesRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $entry = $this->invoiceEntriesRepository->find((int) $data['invoice_entry_id']);
        if ($entry === null) {
            return $response->withStatus(404);
        }

        $supply = $this->suppliesRepository->find((int) $data['supply_id']);
        if ($supply === null) {
            return $response->withStatus(404);
        }

        $supplier = $entry->getInvoice()->getSupplier();
        $description = $entry->getDescription();
        $invoiceId = $entry->getInvoice()->getId();

        // Find or create the SupplyAlias
        $alias = $this->supplyAliasesRepository->findBySupplierAndDescription($supplier->getId(), $description);
        if ($alias === null) {
            $alias = new SupplyAlias();
            $alias->setSupply($supply);
            $alias->setSupplier($supplier);
            $alias->setDescription($description);
            $this->supplyAliasesRepository->persist($alias);
        }

        // Backfill all InvoiceEntry rows with the same supplier + description
        $this->invoiceEntriesRepository->linkAllBySupplierAndDescription($supplier->getId(), $description, $alias);

        return $response->withHeader('Location', '/admin/invoices/view?id=' . $invoiceId)->withStatus(302);
    }
}
```

- [ ] **Step 2: Verify**

On an invoice detail page, click "Σύνδεση" on an unlinked entry, pick a supply, and save. The entry should now show the supply name. Navigate to another invoice that has the same supplier+description — that entry should also be linked automatically.

- [ ] **Step 3: Commit**

```bash
git add src/Application/Actions/Admin/LinkInvoiceEntry.php
git commit -m "feat: add LinkInvoiceEntry action — creates SupplyAlias and backfills all matching entries"
```

---

## Task 13: Supply aliases list + price graph + delete

**Files:**
- Create: `src/Application/Actions/Admin/SupplyAliases.php`
- Create: `src/Application/Actions/Admin/DeleteSupplyAlias.php`
- Create: `src/templates/admin/supply_aliases.twig`

The supply aliases page lists all aliases with a Chart.js price graph per alias (x = invoice date, y = unit_price from linked InvoiceEntry records).

- [ ] **Step 1: Create `SupplyAliases` action**

`src/Application/Actions/Admin/SupplyAliases.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SupplyAliasesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class SupplyAliases
{
    public function __construct(
        private SupplyAliasesRepository $supplyAliasesRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $aliases = $this->supplyAliasesRepository->findBy([], ['description' => 'ASC']);

        return $this->twig->render($response, 'admin/supply_aliases.twig', [
            'aliases' => $aliases,
        ]);
    }
}
```

- [ ] **Step 2: Create `DeleteSupplyAlias` action**

`src/Application/Actions/Admin/DeleteSupplyAlias.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SupplyAliasesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteSupplyAlias
{
    public function __construct(
        private SupplyAliasesRepository $supplyAliasesRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $alias = $this->supplyAliasesRepository->find((int) ($params['id'] ?? 0));

        if ($alias !== null) {
            $this->supplyAliasesRepository->delete($alias);
        }

        return $response->withHeader('Location', '/admin/supply-aliases')->withStatus(302);
    }
}
```

- [ ] **Step 3: Create the template**

`src/templates/admin/supply_aliases.twig`:

```twig
{% extends 'admin/skeleton.twig' %}

{% set activeNavLink = 'supply-aliases' %}

{% block content %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/admin/" class="text-decoration-none"><i class="bi bi-house"></i></a>
    </li>
    <li class="breadcrumb-item active">Ψευδώνυμα προμηθειών</li>
</ol>

<div class="pb-1 mb-3 border-bottom">
    <h1 class="h2">{{'Ψευδώνυμα προμηθειών'|_}}</h1>
</div>

{% for alias in aliases %}
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="card-title mb-1">
                        <a href="/admin/supplies/update?id={{alias.getSupply.getId}}" class="text-decoration-none">
                            {{alias.getSupply.getName}}
                        </a>
                    </h5>
                    <p class="text-muted mb-0 small">
                        {{alias.getSupplier.getName}} · «{{alias.getDescription}}»
                    </p>
                </div>
                <a href="/admin/supply-aliases/delete?id={{alias.getId}}" class="btn btn-sm btn-outline-danger confirm">
                    <i class="bi bi-trash"></i>
                </a>
            </div>

            {% if aliasEntries[alias.getId] is defined and aliasEntries[alias.getId] is not empty %}
                <canvas id="chart-{{alias.getId}}" class="mt-3" height="80"></canvas>
            {% else %}
                <p class="text-muted small mt-2 mb-0">{{'Δεν υπάρχουν δεδομένα τιμής.'|_}}</p>
            {% endif %}
        </div>
    </div>
{% else %}
    <p class="text-muted">{{'Δεν υπάρχουν ψευδώνυμα ακόμα.'|_}}</p>
{% endfor %}

{% endblock %}

{% block javascript %}
    {{parent()}}
    {% if aliases is not empty %}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.js"></script>
        <script>
        {% for alias in aliases %}
            {% if aliasEntries[alias.getId] is defined and aliasEntries[alias.getId] is not empty %}
            new Chart(document.getElementById('chart-{{alias.getId}}'), {
                type: 'line',
                data: {
                    labels: [{% for e in aliasEntries[alias.getId] %}"{{e.getInvoice.getDate.format('Y-m-d')}}"{% if not loop.last %},{% endif %}{% endfor %}],
                    datasets: [{
                        label: 'Τιμή (€)',
                        data: [{% for e in aliasEntries[alias.getId] %}{{e.getUnitPrice}}{% if not loop.last %},{% endif %}{% endfor %}],
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        fill: false,
                    }]
                },
                options: { scales: { y: { beginAtZero: false } } }
            });
            {% endif %}
        {% endfor %}
        </script>
    {% endif %}
{% endblock %}
```

The template needs `aliasEntries` — a map of `alias_id → InvoiceEntry[]` sorted by invoice date. Update the `SupplyAliases` action to supply this data:

- [ ] **Step 4: Update `SupplyAliases` action to fetch price data**

Replace the `__invoke` method in `src/Application/Actions/Admin/SupplyAliases.php`:

```php
    public function __invoke(Request $request, Response $response): Response
    {
        $aliases = $this->supplyAliasesRepository->findBy([], ['description' => 'ASC']);

        // Build aliasEntries: alias_id => InvoiceEntry[] sorted by invoice date ASC
        $aliasEntries = [];
        foreach ($aliases as $alias) {
            $entries = $this->supplyAliasesRepository->getEntityManager()
                ->createQuery(
                    'SELECT e FROM Domain\Entities\InvoiceEntry e
                     JOIN e.invoice i
                     WHERE e.supplyAlias = :alias
                     ORDER BY i.date ASC'
                )
                ->setParameter('alias', $alias)
                ->getResult();
            $aliasEntries[$alias->getId()] = $entries;
        }

        return $this->twig->render($response, 'admin/supply_aliases.twig', [
            'aliases'      => $aliases,
            'aliasEntries' => $aliasEntries,
        ]);
    }
```

- [ ] **Step 5: Verify**

Navigate to `https://sop.ddev.site/admin/supply-aliases`. After creating at least one alias via the link-entry flow, the card should appear. After scanning multiple invoices with the same supplier+description, the Chart.js graph should appear with price points.

- [ ] **Step 6: Commit**

```bash
git add src/Application/Actions/Admin/SupplyAliases.php src/Application/Actions/Admin/DeleteSupplyAlias.php src/templates/admin/supply_aliases.twig
git commit -m "feat: add SupplyAliases list with price graph and DeleteSupplyAlias action"
```

---

## Task 14: Sidebar nav entries

**Files:**
- Modify: `src/templates/admin/skeleton.twig`

- [ ] **Step 1: Add nav entries**

In `src/templates/admin/skeleton.twig`, find the `Προμηθευτές` nav item:

```twig
<li class="nav-item">
    <a class="nav-link {{activeNavLink == 'suppliers' ? 'active' : ''}}" href="/admin/suppliers">
        Προμηθευτές
    </a>
</li>
```

Add the two new entries immediately after it:

```twig
<li class="nav-item">
    <a class="nav-link {{activeNavLink == 'invoices' ? 'active' : ''}}" href="/admin/invoices">
        Τιμολόγια
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{activeNavLink == 'supply-aliases' ? 'active' : ''}}" href="/admin/supply-aliases">
        Ψευδώνυμα προμηθειών
    </a>
</li>
```

- [ ] **Step 2: Verify**

Reload any admin page — both "Τιμολόγια" and "Ψευδώνυμα προμηθειών" should appear in the sidebar. The active state should highlight on the respective pages.

- [ ] **Step 3: Commit**

```bash
git add src/templates/admin/skeleton.twig
git commit -m "feat: add Τιμολόγια and Ψευδώνυμα προμηθειών to admin sidebar"
```

---

## Task 15: Run the details migration

**Files:**
- (already created in a previous session) `migrations/add_details_to_suppliers.php`

This migration adds the `details` jsonb column to the `suppliers` table (needed for `Supplier.details` persisted by `ConfirmInvoice`).

- [ ] **Step 1: Run the migration**

```bash
ddev exec php migrations/add_details_to_suppliers.php
```

Expected output:
```
Done: added 'details' jsonb column to suppliers.
```

- [ ] **Step 2: Smoke test the full flow**

1. Navigate to `https://sop.ddev.site/admin/invoices/scan`
2. Upload a photo of a supplier invoice
3. The review page should show pre-filled data with supplier matched or warning shown
4. Adjust any incorrect fields and click "Αποθήκευση"
5. The invoice detail page should show all line items
6. Click "Σύνδεση" on an unlinked entry, pick a supply, save
7. Navigate to `https://sop.ddev.site/admin/supply-aliases` — the alias card should appear

- [ ] **Step 3: Commit (if any files were touched)**

If `add_details_to_suppliers.php` was not yet committed from before:

```bash
git add migrations/add_details_to_suppliers.php
git commit -m "feat: migrate — add details jsonb column to suppliers"
```
