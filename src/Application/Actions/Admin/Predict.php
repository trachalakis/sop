<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTime;
use DateTimeImmutable;
use DateInterval;
use Application\Services\OrdersReportService;
use Domain\Repositories\OrdersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Predict
{
    public function __construct(
        private OrdersRepository $ordersRepository,
        private OrdersReportService $ordersReportService,
        private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        $date = new DateTime($queryParams['date'] ?? 'today');

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

        $prediction = $this->weightedAverage($rawReports);

        return $this->twig->render(
            $response,
            'admin/predict.twig',
            [
                'date'       => $date,
                'prev'       => (clone $date)->sub(new DateInterval('P1D')),
                'next'       => (clone $date)->add(new DateInterval('P1D')),
                'prediction' => $prediction,
            ]
        );
    }

    /**
     * Apply a descending weighted average to an ordered list of reports.
     *
     * The first report (most recent weekday) gets weight N, the second gets
     * N-1, …, the last gets 1, where N = count($reports).
     */
    private function weightedAverage(array $reports): array
    {
        $empty = [
            'sales'         => 0.0,
            'salesTakeAway' => 0.0,
            'coversAdults'  => 0.0,
            'coversMinors'  => 0.0,
            'totalWeight'   => 0.0,
            'foodCost'      => 0.0,
            'menuSections'  => [],
            'servedPlates'  => 0.0,
            'servedDrinks'  => 0.0,
        ];

        if (empty($reports)) {
            return $empty;
        }

        $n           = count($reports);
        $totalWeight = ($n * ($n + 1)) / 2; // sum of 1..N

        $scalars = ['sales', 'salesTakeAway', 'coversAdults', 'coversMinors',
                    'totalWeight', 'foodCost', 'servedPlates', 'servedDrinks'];

        $prediction = array_fill_keys($scalars, 0.0);

        foreach ($reports as $i => $data) {
            $w = ($n - $i) / $totalWeight; // index 0 → weight N, index N-1 → weight 1
            foreach ($scalars as $key) {
                $prediction[$key] += $data[$key] * $w;
            }
        }

        // menuSections: weighted average per section and per item.
        // Sections/items unseen in a given week contribute 0 for that week.
        $allSectionIds = [];
        foreach ($reports as $data) {
            foreach (array_keys($data['menuSections']) as $sectionId) {
                $allSectionIds[$sectionId] = true;
            }
        }

        $menuSections = [];
        foreach (array_keys($allSectionIds) as $sectionId) {
            // Collect all item IDs seen across all weeks for this section
            $allItemIds  = [];
            $sectionMeta = null;
            foreach ($reports as $data) {
                if (!isset($data['menuSections'][$sectionId])) {
                    continue;
                }
                $sectionMeta ??= $data['menuSections'][$sectionId]; // first (most-recent) seen = metadata source
                foreach (array_keys($data['menuSections'][$sectionId]['menuItems']) as $itemId) {
                    $allItemIds[$itemId] = true;
                }
            }

            $sectionCount = 0.0;
            $sectionSales = 0.0;
            foreach ($reports as $i => $data) {
                $w   = ($n - $i) / $totalWeight;
                $sec = $data['menuSections'][$sectionId] ?? null;
                $sectionCount += ($sec['count'] ?? 0) * $w;
                $sectionSales += ($sec['sales'] ?? 0) * $w;
            }

            $menuItems = [];
            foreach (array_keys($allItemIds) as $itemId) {
                $itemMeta   = null;
                $itemCount  = 0.0;
                $itemWeight = 0.0;
                $itemSales  = 0.0;

                foreach ($reports as $i => $data) {
                    $w    = ($n - $i) / $totalWeight;
                    $item = $data['menuSections'][$sectionId]['menuItems'][$itemId] ?? null;
                    if ($item !== null && $itemMeta === null) {
                        $itemMeta = $item; // first (most-recent) seen = metadata source
                    }
                    $itemCount  += ($item['count']  ?? 0) * $w;
                    $itemWeight += ($item['weight'] ?? 0) * $w;
                    $itemSales  += ($item['sales']  ?? 0) * $w;
                }

                if ($itemMeta !== null) {
                    $menuItems[$itemId] = [
                        'menuItem' => $itemMeta['menuItem'],
                        'count'    => $itemCount,
                        'weight'   => $itemWeight,
                        'sales'    => $itemSales,
                    ];
                }
            }

            if ($sectionMeta !== null) {
                $menuSections[$sectionId] = [
                    'menuSection' => $sectionMeta['menuSection'],
                    'count'       => $sectionCount,
                    'sales'       => $sectionSales,
                    'menuItems'   => $menuItems,
                ];
            }
        }

        uasort($menuSections, fn($a, $b) => $a['menuSection']->getPosition() <=> $b['menuSection']->getPosition());

        $prediction['menuSections'] = $menuSections;

        return $prediction;
    }
}
