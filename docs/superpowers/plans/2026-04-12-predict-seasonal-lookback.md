# Predict Seasonal Lookback Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expand the same-weekday lookback window in `Predict.php` from 8 weeks to 2 years so the prediction works correctly for seasonally-operated restaurants.

**Architecture:** A single loop-limit change in `Predict.php`'s `__invoke()`. The existing skip-empty logic already handles off-season gaps — extending the window to 104 weeks lets it traverse them and find up to 8 non-empty same-weekday reports from any point in the past 2 years.

**Tech Stack:** PHP 8, Slim 4. No new dependencies.

---

### Task 1: Extend the lookback window to 104 weeks

**Files:**
- Modify: `src/Application/Actions/Admin/Predict.php` (the `for` loop in `__invoke()`)

- [ ] **Step 1: Apply the change**

In `src/Application/Actions/Admin/Predict.php`, find the `__invoke()` method. The current loop looks like this (around line 32):

```php
        // Collect reports for up to 8 past same-weekday dates, most recent first.
        $rawReports = [];
        for ($weeksBack = 1; $weeksBack <= 8; $weeksBack++) {
            $pastDate = (clone $date)->sub(new DateInterval("P{$weeksBack}W"));
            $orders   = $this->ordersRepository->findByDate($pastDate);

            if (count($orders) === 0) {
                continue; // restaurant was closed or no data — skip this week
            }

            $end = (new DateTimeImmutable($pastDate->format('Y-m-d') . ' 05:00:00'))
                ->add(new DateInterval('PT21H'));

            $rawReports[] = $this->ordersReportService->buildReport($orders, $end);
        }
```

Replace it with:

```php
        // Collect up to 8 non-empty same-weekday reports, looking back as far as
        // 104 weeks (2 years) to handle seasonally-operated restaurants.
        $rawReports = [];
        for ($weeksBack = 1; $weeksBack <= 104; $weeksBack++) {
            if (count($rawReports) === 8) {
                break;
            }

            $pastDate = (clone $date)->sub(new DateInterval("P{$weeksBack}W"));
            $orders   = $this->ordersRepository->findByDate($pastDate);

            if (count($orders) === 0) {
                continue; // restaurant was closed or no data — skip this week
            }

            $end = (new DateTimeImmutable($pastDate->format('Y-m-d') . ' 05:00:00'))
                ->add(new DateInterval('PT21H'));

            $rawReports[] = $this->ordersReportService->buildReport($orders, $end);
        }
```

- [ ] **Step 2: Commit**

```bash
git add src/Application/Actions/Admin/Predict.php
git commit -m "feat: extend predict lookback to 104 weeks for seasonal restaurants"
```
