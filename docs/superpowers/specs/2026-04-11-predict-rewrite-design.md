# Design: Predict.php Rewrite with Weighted Moving Average

**Date:** 2026-04-11

## Problem

`Predict.php` only predicts 5 metrics using a naive average of 2 data points (same weekday -1 and -2 weeks). It has a dead `dd('xoxo')` statement, a buggy `ordersReport()` private method (duplicated and drifted from `Report.php`), and a broken `predict.twig` that references variables never passed by the action. The goal is to replace it with a statistically sound prediction covering all order-derived metrics from `Report.php`.

## Solution Overview

1. Extract order-aggregation logic from `Report.php` into a shared `OrdersReportService`
2. Refactor `Report.php` to use the service (no behaviour change)
3. Rewrite `Predict.php` to use the service + a weighted moving average over 8 same-weekday data points
4. Rewrite `predict.twig` to display all predicted metrics

## New File: `src/Application/Services/OrdersReportService.php`

Single public method:

```php
public function buildReport(iterable $orders, DateTimeInterface $end): array
```

**Parameters:**
- `$orders` — iterable of `Order` entities for the period
- `$end` — reference date used for ingredient cost lookups (`$recipe->getFoodCost($end)`)

**Returns:**
```php
[
    'sales'          => float,   // total order sales (all tables including take-away)
    'salesTakeAway'  => float,   // sales excluding take-away table, non-drinks-only orders
    'coversAdults'   => int,
    'coversMinors'   => int,
    'totalWeight'    => float,   // sum of (weight × quantity) across all order entries
    'foodCost'       => float,   // recipe-based food cost
    'menuSections'   => [        // keyed by section ID
        $sectionId => [
            'menuSection' => MenuSection,
            'count'       => int,
            'sales'       => float,
            'menuItems'   => [   // keyed by menu item ID
                $itemId => [
                    'menuItem' => MenuItem,
                    'count'    => int,
                    'weight'   => float,
                    'sales'    => float,
                ]
            ]
        ]
    ],
    'servedPlates'   => int,
    'servedDrinks'   => int,
]
```

**Logic** is extracted verbatim from `Report.php`'s inline metric computation. `standardDeviation` and per-hour chart data are not included — they are specific to the daily report view and do not generalise to a predicted aggregate.

**Dependencies:** `RecipesRepository` (injected via constructor).

**DI binding** added to `app/dependencies.php`:
```php
OrdersReportService::class => fn($c) => new OrdersReportService(
    $c->get(RecipesRepository::class)
),
```

## Changes to `Report.php`

- Inject `OrdersReportService` via constructor
- Remove inline metric-computation loop and replace with:
  ```php
  $report = $this->ordersReportService->buildReport($orders, $end);
  ```
- Unpack `$report` into the existing template variables (`$sales`, `$coversAdults`, etc.) so the `report.twig` template is untouched
- Delete the private `ordersReport()` method
- `standardDeviation`, per-hour chart data, and scan/labour metrics remain computed inline in `Report.php` (they are not part of the service)

## Rewritten `Predict.php`

**Constructor dependencies:** `OrdersRepository`, `OrdersReportService`, `Twig`

**Prediction algorithm:**

1. Parse the `?date=` query param as the target date
2. Build 8 past same-weekday dates: target − 1 week through target − 8 weeks
3. For each past date, call `$ordersRepository->findByDate($date)` and then `$service->buildReport($orders, $end)` where `$end` is that date's day-end (05:00 + 21h)
4. Skip any date that returns zero orders (restaurant closed or data unavailable)
5. Assign descending weights to the non-empty reports: most recent = highest weight, oldest = 1. Total weight = sum of assigned weights.
6. Apply weighted average to all scalar metrics (`sales`, `salesTakeAway`, `coversAdults`, `coversMinors`, `totalWeight`, `foodCost`, `servedPlates`, `servedDrinks`)
7. For `menuSections`: build a union of all section/item IDs seen across non-empty weeks. For each item, apply the same weighted average (missing in a given week = 0 for that week). Section/item objects are taken from the most recent week the item appears in. Sections are sorted by `menuSection->getPosition()` as in Report.php.
8. Pass `$prediction` array + `$date`/`$prev`/`$next` to the template

**Weight formula:** weights are `[N, N-1, …, 1]` where N = number of non-empty weeks (max 8). This ensures the most recent same-weekday always has the greatest influence.

## Rewritten `predict.twig`

Layout mirrors `report.twig`. Sections:

- **Header:** date title + prev/next navigation + date picker form (unchanged from current)
- **Top-line row:** predicted sales (€), take-away sales (€), adult covers, minor covers, cover spend (sales ÷ total covers, rounded to 1 decimal)
- **Second row:** total weight (Kg), food cost (€), served plates, served drinks
- **Per-section grid:** same card layout as `report.twig` — section name / item count / sales, then per item: name / count / weight / sales

All values are rounded to 1 decimal place for display.

## Out of Scope

- `standardDeviation` and per-hour chart predictions
- Labour/scan metrics (`manHours`, `salaries`, etc.)
- `report.twig` (untouched)
- `TakeOutApp` or any other action
