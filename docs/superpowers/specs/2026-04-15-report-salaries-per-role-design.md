# Design: Per-Role Salary Breakdown on Report Page

**Date:** 2026-04-15

## Overview

The report page currently shows total, BOH, and FOH salaries. This design adds a per-role salary breakdown, showing how much salary was attributed to each role for the period.

Role data comes from the roles snapshot stored on each `Scan` at clock-in time (`Scan::getRoles()` — a nullable `string[]`). Scans without a snapshot (null roles) are silently skipped for the per-role breakdown. Full salary is attributed to each role when a scan has multiple roles.

## New Service: `ScansReportService`

**File:** `src/Application/Services/ScansReportService.php`

A new service with a single public method:

```php
public function buildReport(iterable $scans): array
```

**Input:** The scans collection for the period (same `$scans` already fetched in `Report.php`).

**Output:**
```php
[
    'manHours'        => int,
    'manMinutes'      => int,
    'manSeconds'      => int,
    'salaries'        => float,
    'bohSalaries'     => float,
    'fohSalaries'     => float,
    'salariesPerRole' => array<string, float>,  // keyed by role slug
]
```

The method contains all scan-iteration logic currently inlined in `Report.php`, plus the new per-role accumulation:

```php
if ($scan->getRoles() !== null) {
    foreach ($scan->getRoles() as $role) {
        $salariesPerRole[$role] = ($salariesPerRole[$role] ?? 0) + $scan->getSalary();
    }
}
```

BOH/FOH salaries continue to be derived from the user's current roles (existing behaviour — `$scan->getUser()->getRoles()`), unchanged.

## `Report.php` Changes

- Inject `ScansReportService` via constructor.
- Replace the existing inline scans loop with `$scansReportService->buildReport($scans)`.
- Unpack all keys from the result (same variable names as today).
- Pass `salariesPerRole` to the Twig template.

## `report.twig` Changes

After the existing FOH/BOH salary lines, add per-role output (only rendered if `salariesPerRole` is non-empty):

```
waiter: €120 · bartender: €80 · cook: €200
```

Role slugs are displayed as-is (no translation needed).

## DI Container

Bind `ScansReportService` in `app/dependencies.php` following the project's explicit registration pattern:

```php
ScansReportService::class => function (ContainerInterface $c) {
    return new ScansReportService();
},
```

## Out of Scope

- No changes to BOH/FOH salary calculation logic.
- No UI changes beyond the new per-role line in the totals section.
- No changes to the scans fetch query in `Report.php`.
