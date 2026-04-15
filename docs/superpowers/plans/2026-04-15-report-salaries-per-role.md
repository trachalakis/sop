# Report Per-Role Salary Breakdown Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract scan/salary logic from `Report.php` into a new `ScansReportService` and add a per-role salary breakdown to the report page.

**Architecture:** A new `ScansReportService` takes the scans collection and returns all salary metrics including `salariesPerRole` (keyed by role slug, full salary per role from the scan snapshot). `Report.php` is updated to call the service and pass `salariesPerRole` to the template. The separate `bohSalaries`/`fohSalaries` variables are removed — BOH/FOH are now just entries in `salariesPerRole`.

**Tech Stack:** PHP 8, Slim 4, Doctrine ORM 3, Twig 3, DDEV

---

## Files

- Create: `src/Application/Services/ScansReportService.php`
- Modify: `app/dependencies.php` — register `ScansReportService`
- Modify: `src/Application/Actions/Admin/Report.php` — inject service, remove inline loop, pass `salariesPerRole`
- Modify: `src/templates/admin/report.twig` — replace BOH/FOH lines with per-role breakdown

---

### Task 1: Create `ScansReportService`

**Files:**
- Create: `src/Application/Services/ScansReportService.php`

- [ ] **Step 1: Create the service file**

```php
<?php

declare(strict_types=1);

namespace Application\Services;

use Domain\Entities\Scan;

final class ScansReportService
{
    /**
     * Compute all scan-derived metrics for a set of scans.
     *
     * @param iterable $scans  Scan entities for the period.
     * @return array{
     *   manHours: int,
     *   manMinutes: int,
     *   manSeconds: int,
     *   salaries: float,
     *   salariesPerRole: array<string, float>,
     * }
     */
    public function buildReport(iterable $scans): array
    {
        $manHours        = 0;
        $manMinutes      = 0;
        $manSeconds      = 0;
        $salaries        = 0.0;
        $salariesPerRole = [];

        foreach ($scans as $scan) {
            $interval = $scan->getInterval();

            if ($interval === null) {
                continue;
            }

            $manHours   += $interval->h;
            $manMinutes += $interval->i;
            $manSeconds += $interval->s;

            if ($manSeconds >= 60) {
                $manMinutes++;
                $manSeconds -= 60;
            }

            if ($manMinutes >= 60) {
                $manHours++;
                $manMinutes -= 60;
            }

            $salaries += $scan->getSalary();

            if ($scan->getRoles() !== null) {
                foreach ($scan->getRoles() as $role) {
                    $salariesPerRole[$role] = ($salariesPerRole[$role] ?? 0.0) + $scan->getSalary();
                }
            }
        }

        return [
            'manHours'        => $manHours,
            'manMinutes'      => $manMinutes,
            'manSeconds'      => $manSeconds,
            'salaries'        => $salaries,
            'salariesPerRole' => $salariesPerRole,
        ];
    }
}
```

- [ ] **Step 2: Verify the file parses**

```bash
ddev exec php -r "require 'vendor/autoload.php'; new \Application\Services\ScansReportService(); echo 'OK';"
```

Expected output: `OK`

- [ ] **Step 3: Commit**

```bash
git add src/Application/Services/ScansReportService.php
git commit -m "feat: add ScansReportService"
```

---

### Task 2: Register `ScansReportService` in the DI container

**Files:**
- Modify: `app/dependencies.php`

- [ ] **Step 1: Add the `use` import**

At the top of `app/dependencies.php`, alongside the existing `use Application\Services\OrdersReportService;` line, add:

```php
use Application\Services\ScansReportService;
```

- [ ] **Step 2: Register the service**

In `app/dependencies.php`, after the `OrdersReportService` binding (around line 234), add:

```php
ScansReportService::class => function (ContainerInterface $c) {
    return new ScansReportService();
},
```

- [ ] **Step 3: Verify the app boots**

```bash
ddev exec php -r "require 'vendor/autoload.php'; echo 'OK';"
```

Expected output: `OK`

- [ ] **Step 4: Commit**

```bash
git add app/dependencies.php
git commit -m "feat: register ScansReportService in DI container"
```

---

### Task 3: Update `Report.php` to use `ScansReportService`

**Files:**
- Modify: `src/Application/Actions/Admin/Report.php`

- [ ] **Step 1: Inject `ScansReportService` and replace the inline scans loop**

Replace the entire `Report.php` file content with the following. The key changes are:
- Add `ScansReportService` import and constructor parameter
- Replace the inline scans loop (lines 116–150) with a single service call
- Remove `$bohSalaries` and `$fohSalaries` variables entirely
- Add `salariesPerRole` to the template variables
- Remove `bohSalaries` and `fohSalaries` from template variables

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Datetime;
use DateTimeImmutable;
use Application\Services\OrdersReportService;
use Application\Services\ScansReportService;
use Domain\Repositories\ScansRepository;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\MenuSectionsRepository;
use Doctrine\Common\Collections\Criteria;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Report
{
    public function __construct(
        private MenuSectionsRepository $menuSectionsRepository,
        private OrdersRepository $ordersRepository,
        private OrdersReportService $ordersReportService,
        private ScansReportService $scansReportService,
        private ScansRepository $scansRepository,
        private Twig $twig
    ) {
    }

	public function __invoke(Request $request, Response $response)
	{
		$ordersRepository = $this->ordersRepository;
		$queryParams = $request->getQueryParams();
		$filter = $queryParams['filter'] ?? null;

        if (!empty($filter['start']) && !empty($filter['end'])) {
	        $start = new Datetime($filter['start']);
	        $end = new Datetime($filter['end']);
	    } else if (!empty($filter['start']) && empty($filter['end'])){
            $start = new Datetime($filter['start'] . ' 05:00');
            $end = (clone $start)->add(new \DateInterval('PT21H'));
        } else if ((new Datetime())->format('G') <= 4) {
            $start = new Datetime('yesterday 05:00');
            $end = (clone $start)->add(new \DateInterval('PT21H'));
        } else {
            $start = new Datetime('today 05:00');
            $end = (clone $start)->add(new \DateInterval('PT21H'));
        }

        $start = DateTimeImmutable::createFromMutable($start);
        $end = DateTimeImmutable::createFromMutable($end);

	    $criteria = new Criteria;
        $criteria->andWhere(Criteria::expr()->gte('createdAt', $start));
	    $criteria->andWhere(Criteria::expr()->lte('createdAt', $end));
        $orders = $ordersRepository->matching($criteria->orderBy(['createdAt' => 'asc']));

        if (isset($filter['service']) && $filter['service'] == 'lunch') {
            $orders = $orders->filter(function ($order) {
                return $order->getCreatedAt()->format('G') < 18;
            });
        }

        if (isset($filter['service']) && $filter['service'] == 'dinner') {
            $orders = $orders->filter(function ($order) {
                return $order->getCreatedAt()->format('G') >= 18;
            });
        }

        $report = $this->ordersReportService->buildReport($orders, $end);

        $sales          = $report['sales'];
        $salesTakeAway  = $report['salesTakeAway'];
        $coversAdults   = $report['coversAdults'];
        $coversMinors   = $report['coversMinors'];
        $totalWeight    = $report['totalWeight'];
        $foodCost       = $report['foodCost'];
        $menuSections   = $report['menuSections'];
        $servedPlates   = $report['servedPlates'];
        $servedDrinks   = $report['servedDrinks'];
        $servedMenuItems = $servedPlates + $servedDrinks;

        $coversPerHourData = [];
        $ordersPerHourData = [];
        $salesPerHourData  = [];

        foreach ($orders as $order) {
            if ($order->getTable() === null) {
                continue;
            }
            if (isset($coversPerHourData[$order->getCreatedAt()->format('G')])) {
                $ordersPerHourData[$order->getCreatedAt()->format('G')] += 1;
                $coversPerHourData[$order->getCreatedAt()->format('G')] += $order->getAdults() + $order->getMinors();
                $salesPerHourData[$order->getCreatedAt()->format('G')]  += $order->getPrice();
            } else {
                $ordersPerHourData[$order->getCreatedAt()->format('G')] = 1;
                $coversPerHourData[$order->getCreatedAt()->format('G')] = $order->getAdults() + $order->getMinors();
                $salesPerHourData[$order->getCreatedAt()->format('G')]  = $order->getPrice();
            }
        }

        $chartLabels = array_keys($coversPerHourData);
        sort($chartLabels);

        ksort($coversPerHourData);
        ksort($salesPerHourData);
        ksort($ordersPerHourData);

        $start = DateTime::createFromImmutable($start);
        $end = DateTime::createFromImmutable($end);

        $criteria = new Criteria;
        $criteria->andWhere(Criteria::expr()->gte('checkIn', $start));
	    $criteria->andWhere(Criteria::expr()->lte('checkIn', $end));
        $scans = $this->scansRepository->matching($criteria);

        $scansReport     = $this->scansReportService->buildReport($scans);
        $manHours        = $scansReport['manHours'];
        $manMinutes      = $scansReport['manMinutes'];
        $manSeconds      = $scansReport['manSeconds'];
        $salaries        = $scansReport['salaries'];
        $salariesPerRole = $scansReport['salariesPerRole'];

        /*** Statistics ***/
        $standardDeviation = 0;
        if (count($orders) > 10) {
            $salesMean = $sales / count($orders);
            $squaresSum = 0;
            foreach($orders as $order) {
                $squaresSum += ($order->getPrice() - $salesMean) ** 2;
            }
            $standardDeviation = round(sqrt($squaresSum / (count($orders) - 1)), 2);
        }

        $oneYearAgo = (clone $start)->sub(new \DateInterval('P1Y'));

        return $this->twig->render(
            $response,
            'admin/report.twig',
            [
                'filter' => $filter,
            	'start'=> $start,
                'end' => $end,
                'previousDay' => (clone $start)->sub(new \DateInterval('P1D')),
                'nextDay' => (clone $start)->add(new \DateInterval('P1D')),
            	'orders' => $orders,
            	'sales' => $sales,
            	'salesTakeAway' => $salesTakeAway,
            	'coversAdults' => $coversAdults,
            	'coversMinors' => $coversMinors,
                'totalWeight' => $totalWeight,
                'menuSections' => $menuSections,

                'manHours' => $manHours,
                'manMinutes' => $manMinutes,
                'manSeconds' => $manSeconds,

                'salaries' => $salaries,
                'salariesPerRole' => $salariesPerRole,

                'ordersPerHourData' => $ordersPerHourData,
                'coversPerHourData' => $coversPerHourData,
                'salesPerHourData' => $salesPerHourData,
                'chartLabels' => $chartLabels,

                'oneYearAgo' => $oneYearAgo,

                'standardDeviation' => $standardDeviation,

                'foodCost' => $foodCost,

                'servedMenuItems' => $servedMenuItems,
                'servedPlates' => $servedPlates,
                'servedDrinks' => $servedDrinks,
            ]
        );
	}
}
```

- [ ] **Step 2: Verify the app boots**

```bash
ddev exec php -r "require 'vendor/autoload.php'; echo 'OK';"
```

Expected output: `OK`

- [ ] **Step 3: Commit**

```bash
git add src/Application/Actions/Admin/Report.php
git commit -m "feat: use ScansReportService in Report action"
```

---

### Task 4: Update `report.twig` to show per-role salary breakdown

**Files:**
- Modify: `src/templates/admin/report.twig`

- [ ] **Step 1: Replace the BOH/FOH salary lines with per-role breakdown**

In `src/templates/admin/report.twig`, replace lines 81–82:

```twig
	BOH Salaries: <strong>&euro;{{bohSalaries|round}}</strong> &bull;
	FOH Salaries: <strong>&euro;{{fohSalaries|round}}</strong> &bull;
```

With:

```twig
	{% for role, amount in salariesPerRole %}
		{{role}}: <strong>&euro;{{amount|round}}</strong> &bull;
	{% endfor %}
```

- [ ] **Step 2: Verify the page loads**

Open `https://sop.ddev.site/admin/report` in a browser and confirm:
- The totals section renders without errors
- BOH/FOH lines are gone
- Per-role lines appear if any scans with role snapshots exist for the current day
- If no scans have role snapshots yet, the section is simply empty (no error)

- [ ] **Step 3: Commit**

```bash
git add src/templates/admin/report.twig
git commit -m "feat: show per-role salary breakdown on report page"
```
