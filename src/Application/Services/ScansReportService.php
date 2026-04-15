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
