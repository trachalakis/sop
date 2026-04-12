# Design: Seasonal Lookback for Predict.php

**Date:** 2026-04-12

## Problem

`Predict.php` collects up to 8 non-empty same-weekday reports by looking back one week at a time, up to a maximum of 8 weeks. For a seasonally-operated restaurant (e.g. open only May–October), the 8-week window often falls entirely in the off-season, returning zero data points and producing an all-zero prediction.

## Solution

Expand the lookback window from 8 weeks to 104 weeks (2 full years), while keeping the 8-result cap on non-empty reports. The algorithm already skips weeks with no orders, so it naturally traverses the off-season gap and finds the 8 most recent same-weekday dates that had actual orders — whether they are in the current season or in the same period one or two years prior.

## Change

**File:** `src/Application/Actions/Admin/Predict.php`

Replace:

```php
for ($weeksBack = 1; $weeksBack <= 8; $weeksBack++) {
    $pastDate = (clone $date)->sub(new DateInterval("P{$weeksBack}W"));
    $orders   = $this->ordersRepository->findByDate($pastDate);

    if (count($orders) === 0) {
        continue;
    }
    // ...
    $rawReports[] = $this->ordersReportService->buildReport($orders, $end);
}
```

With:

```php
for ($weeksBack = 1; $weeksBack <= 104; $weeksBack++) {
    if (count($rawReports) === 8) {
        break;
    }
    $pastDate = (clone $date)->sub(new DateInterval("P{$weeksBack}W"));
    $orders   = $this->ordersRepository->findByDate($pastDate);

    if (count($orders) === 0) {
        continue;
    }
    // ...
    $rawReports[] = $this->ordersReportService->buildReport($orders, $end);
}
```

## Behaviour

- **Year-round restaurants:** No change. The first 8 non-empty weeks are still the 8 most recent consecutive same-weekdays.
- **Seasonal restaurants (mid-season):** Current-season weeks fill most of the 8 slots and receive higher weights; prior-year same-period data fills any remaining slots with lower weights.
- **Seasonal restaurants (start of season / 0–7 current-season weeks available):** The algorithm crosses the off-season gap and draws from the same weekday in the previous year(s). Recency weighting still applies — the most recent non-empty week always gets the highest weight.
- **Worst case (no history at all):** Loop exhausts all 104 weeks, `$rawReports` is empty, `weightedAverage([])` returns the all-zero `$empty` array — same safe behaviour as before.

## Out of Scope

- Changes to `weightedAverage()`, `OrdersReportService`, `Report.php`, or `predict.twig`
- Any configuration for season dates — the skip-empty logic handles this automatically
