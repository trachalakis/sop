<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTime;
use Doctrine\ORM\EntityManager;
use Domain\Entities\PrintJob;
use Domain\Enums\PrintJobStatus;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\PrintersRepository;
use Domain\Repositories\ReservationsRepository;
use Domain\Repositories\TablesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Homepage
{
    private const PAYMENT_LABELS = [
        'CASH' => 'Μετρητά',
        'CREDIT_CARD' => 'Κάρτα',
        'BOTH' => 'Μικτή',
    ];

    public function __construct(
        private OrdersRepository $ordersRepository,
        private ReservationsRepository $reservationsRepository,
        private TablesRepository $tablesRepository,
        private PrintersRepository $printersRepository,
        private EntityManager $entityManager,
        private Twig $twig
    ) { }

    public function __invoke(Request $request, Response $response)
    {
        $now = new DateTime();

        $todaysOrders = $this->ordersRepository->findByDate($now);
        $openTableOrders = $this->ordersRepository->findActiveTableOrders();

        // --- KPI totals ---
        $revenue = 0.0;
        $covers = 0;
        $coverAdults = 0;
        foreach ($todaysOrders as $order) {
            $revenue += $order->getPrice();
            $covers += $order->getAdults() + $order->getMinors();
            $coverAdults += $order->getAdults();
        }
        $ordersCount = count($todaysOrders);
        $avg = $ordersCount > 0 ? $revenue / $ordersCount : 0.0;

        // --- Recent orders ---
        $recent = $todaysOrders;
        usort($recent, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        $recentOrders = array_map(fn($o) => $this->presentOrder($o), array_slice($recent, 0, 8));

        // --- Reservations (today, upcoming, not cancelled) ---
        $reservations = $this->reservationsRepository->findByDate($now);
        $upcoming = array_filter(
            $reservations,
            fn($r) => $r->getStatus() !== 'CANCELLED' && $r->getDateTime() >= $now
        );
        usort($upcoming, fn($a, $b) => $a->getDateTime() <=> $b->getDateTime());
        $reservationRows = array_map(fn($r) => $this->presentReservation($r), array_slice($upcoming, 0, 6));

        return $this->twig->render($response, 'admin/homepage.twig', [
            'revenue' => $this->splitAmount($revenue),
            'ordersCount' => $ordersCount,
            'openCount' => count($openTableOrders),
            'covers' => $covers,
            'coverAdults' => $coverAdults,
            'avg' => $this->splitAmount($avg),
            'recentOrders' => $recentOrders,
            'reservations' => $reservationRows,
            'floor' => $this->buildFloorPlan($openTableOrders, $upcoming),
            'system' => $this->buildSystemStatus(count($openTableOrders)),
            'topItems' => $this->buildTopItems($todaysOrders),
            'activity' => $this->buildActivity($todaysOrders, $reservations, $now),
            'chart' => $this->buildChart($todaysOrders, $now),
        ]);
    }

    /** @return array{whole:string, decimals:string} */
    private function splitAmount(float $amount): array
    {
        [$whole, $decimals] = explode(',', number_format($amount, 2, ',', '.'));
        return ['whole' => $whole, 'decimals' => $decimals];
    }

    private function presentOrder($order): array
    {
        $table = $order->getTable();
        $isTakeAway = $table === null;
        $isPaid = $order->getPaidAt() !== null || $order->getStatus() === 'CLOSED';

        return [
            'ticket' => $order->getTicketNumber(),
            'tableLabel' => $isTakeAway ? 'Take-away' : $table->getName(),
            'isTakeAway' => $isTakeAway,
            'waiter' => $isTakeAway ? '—' : ($order->getWaiter()?->getFullName() ?? '—'),
            'covers' => $order->getAdults() + $order->getMinors(),
            'price' => number_format($order->getPrice(), 2, ',', '.'),
            'payment' => $isPaid ? (self::PAYMENT_LABELS[$order->getPaymentMethod()] ?? '—') : '—',
            'statusLabel' => $order->getStatus() === 'OPEN' ? 'Ανοιχτή' : 'Κλειστή',
            'statusOpen' => $order->getStatus() === 'OPEN',
        ];
    }

    private function presentReservation($reservation): array
    {
        $tableNames = [];
        foreach ($reservation->getTables() as $table) {
            $tableNames[] = $table->getName();
        }

        $statusMap = [
            'CONFIRMED' => ['ΟΚ', 'ok'],
            'ARRIVED' => ['Ήρθε', 'ok'],
            'PENDING' => ['Εκκρεμεί', 'pending'],
            'NO_SHOW' => ['Δεν ήρθε', 'pending'],
        ];
        [$statusLabel, $statusKind] = $statusMap[$reservation->getStatus()] ?? [$reservation->getStatus(), 'pending'];

        return [
            'time' => $reservation->getDateTime()->format('H:i'),
            'name' => $reservation->getName(),
            'people' => $reservation->getAdults() + $reservation->getMinors(),
            'tables' => $tableNames ? implode('–', $tableNames) : '—',
            'statusLabel' => $statusLabel,
            'statusKind' => $statusKind,
        ];
    }

    private function buildFloorPlan(array $openTableOrders, array $upcoming): array
    {
        $occupied = [];
        foreach ($openTableOrders as $order) {
            if ($order->getTable()) {
                $occupied[$order->getTable()->getId()] = true;
            }
        }

        $reserved = [];
        foreach ($upcoming as $reservation) {
            foreach ($reservation->getTables() as $table) {
                $reserved[$table->getId()] ??= $reservation->getDateTime()->format('H:i');
            }
        }

        $tables = $this->tablesRepository->findBy(['isActive' => true], ['position' => 'ASC']);
        $rows = [];
        $counts = ['occupied' => 0, 'free' => 0, 'reserved' => 0];
        foreach ($tables as $table) {
            $id = $table->getId();
            if (isset($occupied[$id])) {
                $state = 'occupied';
                $info = '';
            } elseif (isset($reserved[$id])) {
                $state = 'reserved';
                $info = $reserved[$id];
            } else {
                $state = 'free';
                $info = '';
            }
            $counts[$state]++;
            $rows[] = ['name' => $table->getName(), 'state' => $state, 'info' => $info];
        }

        return ['tables' => $rows, 'counts' => $counts];
    }

    private function buildSystemStatus(int $openCount): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('p.printer AS name, COUNT(p.id) AS cnt')
            ->from(PrintJob::class, 'p')
            ->where('p.status = :pending')
            ->setParameter('pending', PrintJobStatus::pending)
            ->groupBy('p.printer')
            ->getQuery()
            ->getResult();
        $queue = [];
        $totalQueue = 0;
        foreach ($rows as $row) {
            $queue[$row['name']] = (int) $row['cnt'];
            $totalQueue += (int) $row['cnt'];
        }

        $printers = $this->printersRepository->findBy(['isActive' => true]);
        $list = [];
        $clear = 0;
        foreach ($printers as $printer) {
            $depth = $queue[$printer->getName()] ?? 0;
            if ($depth === 0) {
                $clear++;
            }
            $list[] = [
                'name' => $printer->getName(),
                'subtitle' => trim(($printer->getPrinterType() ?: $printer->getPrinterAddress() ?: '')) . ' · ' . $depth . ' στην ουρά',
                'ok' => $depth === 0,
                'statusLabel' => $depth === 0 ? 'Online' : 'Σε αναμονή',
            ];
        }

        return [
            'printers' => $list,
            'clear' => $clear,
            'total' => count($printers),
            'openCount' => $openCount,
            'queueTotal' => $totalQueue,
        ];
    }

    private function buildTopItems(array $todaysOrders): array
    {
        $agg = [];
        foreach ($todaysOrders as $order) {
            foreach ($order->getOrderEntries() as $entry) {
                $menuItem = $entry->getMenuItem();
                if ($menuItem === null) {
                    continue;
                }
                $id = $menuItem->getId();
                if (!isset($agg[$id])) {
                    $name = $menuItem->getTranslation('el')?->getName()
                        ?? $menuItem->getTranslation('en')?->getName()
                        ?? '—';
                    $agg[$id] = ['name' => $name, 'qty' => 0, 'revenue' => 0.0];
                }
                $agg[$id]['qty'] += $entry->getQuantity();
                $agg[$id]['revenue'] += $entry->getPrice();
            }
        }

        usort($agg, fn($a, $b) => $b['qty'] <=> $a['qty']);
        $agg = array_slice($agg, 0, 5);
        $max = $agg ? $agg[0]['qty'] : 0;

        return array_map(fn($item) => [
            'name' => $item['name'],
            'qty' => $item['qty'],
            'revenue' => '€' . number_format(round($item['revenue']), 0, ',', '.'),
            'pct' => $max > 0 ? (int) round($item['qty'] / $max * 100) : 0,
        ], $agg);
    }

    private function buildActivity(array $todaysOrders, array $reservations, DateTime $now): array
    {
        $events = [];
        foreach ($todaysOrders as $order) {
            $where = $order->getTable() ? $order->getTable()->getName() : 'Take-away';
            $events[] = [
                'kind' => 'order',
                'ts' => $order->getCreatedAt()->getTimestamp(),
                'title' => 'Νέα παραγγελία',
                'detail' => $where,
                'who' => $order->getWaiter()?->getFullName() ?? '',
            ];
            if ($order->getPaidAt() !== null) {
                $events[] = [
                    'kind' => 'payment',
                    'ts' => $order->getPaidAt()->getTimestamp(),
                    'title' => 'Πληρωμή',
                    'detail' => trim($where . ' €' . number_format($order->getPrice(), 2, ',', '.')),
                    'who' => $order->getWaiter()?->getFullName() ?? '',
                ];
            }
        }
        foreach ($reservations as $reservation) {
            if ($reservation->getStatus() === 'CANCELLED') {
                continue;
            }
            $events[] = [
                'kind' => 'reservation',
                'ts' => $reservation->getCreatedAt()->getTimestamp(),
                'title' => 'Νέα κράτηση',
                'detail' => ($reservation->getAdults() + $reservation->getMinors()) . ' άτομα',
                'who' => trim($reservation->getName() . ' ' . $reservation->getDateTime()->format('H:i')),
            ];
        }

        usort($events, fn($a, $b) => $b['ts'] <=> $a['ts']);
        $events = array_slice($events, 0, 6);

        $nowTs = $now->getTimestamp();
        return array_map(function ($event) use ($nowTs) {
            $mins = max(0, (int) floor(($nowTs - $event['ts']) / 60));
            $event['ago'] = 'πριν ' . $mins . '′';
            return $event;
        }, $events);
    }

    private function buildChart(array $todaysOrders, DateTime $now): array
    {
        $dayStart = new DateTime($now->format('Y-m-d') . ' 05:00:00');
        if ((int) $now->format('H') < 5) {
            $dayStart->modify('-1 day');
        }
        $startTs = $dayStart->getTimestamp();

        $bucketCount = min(24, max(1, (int) floor(($now->getTimestamp() - $startTs) / 3600) + 1));
        $revenue = array_fill(0, $bucketCount, 0.0);
        $orders = array_fill(0, $bucketCount, 0);
        $covers = array_fill(0, $bucketCount, 0);

        foreach ($todaysOrders as $order) {
            $idx = (int) floor(($order->getCreatedAt()->getTimestamp() - $startTs) / 3600);
            $idx = max(0, min($bucketCount - 1, $idx));
            $revenue[$idx] += $order->getPrice();
            $orders[$idx]++;
            $covers[$idx] += $order->getAdults() + $order->getMinors();
        }

        $labels = [];
        $step = max(1, (int) ceil($bucketCount / 7));
        for ($i = 0; $i < $bucketCount; $i += $step) {
            $labels[] = (clone $dayStart)->modify("+{$i} hours")->format('H:i');
        }

        return [
            'revenue' => $this->series($revenue, '€' . number_format(array_sum($revenue), 2, ',', '.'), 'Σύνολο εσόδων · σήμερα'),
            'orders' => $this->series(array_map('floatval', $orders), (string) array_sum($orders), 'Παραγγελίες · σήμερα'),
            'covers' => $this->series(array_map('floatval', $covers), (string) array_sum($covers), 'Καλυμμένες θέσεις · σήμερα'),
            'labels' => $labels,
        ];
    }

    /** Build an SVG line + area path (viewBox 800x220, plot area x:20..780 y:30..190). */
    private function series(array $values, string $value, string $sub): array
    {
        $n = count($values);
        $max = $values ? max($values) : 0.0;
        $points = [];
        foreach ($values as $i => $v) {
            $x = $n <= 1 ? 20 : 20 + ($i / ($n - 1)) * 760;
            $y = $max > 0 ? 190 - ($v / $max) * 160 : 190;
            $points[] = round($x, 1) . ',' . round($y, 1);
        }
        $line = 'M' . implode(' L', $points);
        $lastX = $n <= 1 ? 20 : 780;
        $area = $line . ' L' . $lastX . ',190 L20,190 Z';

        return ['line' => $line, 'area' => $area, 'value' => $value, 'sub' => $sub];
    }
}
