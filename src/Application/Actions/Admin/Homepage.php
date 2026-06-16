<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTime;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\ReservationsRepository;
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
        private Twig $twig
    ) { }

    public function __invoke(Request $request, Response $response)
    {
        $now = new DateTime();

        $todaysOrders = $this->ordersRepository->findByDate($now);
        $openTableOrders = $this->ordersRepository->findActiveTableOrders();

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

        // Latest orders of the business day, newest first
        $recent = $todaysOrders;
        usort($recent, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        $recentOrders = array_map(
            fn($order) => $this->presentOrder($order),
            array_slice($recent, 0, 8)
        );

        // Upcoming, non-cancelled reservations for today
        $reservations = $this->reservationsRepository->findByDate($now);
        $upcoming = array_filter(
            $reservations,
            fn($r) => $r->getStatus() !== 'CANCELLED' && $r->getDateTime() >= $now
        );
        usort($upcoming, fn($a, $b) => $a->getDateTime() <=> $b->getDateTime());
        $reservationRows = array_map(
            fn($r) => $this->presentReservation($r),
            array_slice($upcoming, 0, 6)
        );

        return $this->twig->render($response, 'admin/homepage.twig', [
            'revenue' => $this->splitAmount($revenue),
            'ordersCount' => $ordersCount,
            'openCount' => count($openTableOrders),
            'covers' => $covers,
            'coverAdults' => $coverAdults,
            'avg' => $this->splitAmount($avg),
            'recentOrders' => $recentOrders,
            'reservations' => $reservationRows,
        ]);
    }

    /** @return array{whole:string, decimals:string} e.g. 3248.5 -> {"3.248","50"} */
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

        $status = $reservation->getStatus();
        $statusMap = [
            'CONFIRMED' => ['ΟΚ', 'ok'],
            'ARRIVED' => ['Ήρθε', 'ok'],
            'PENDING' => ['Εκκρεμεί', 'pending'],
            'NO_SHOW' => ['Δεν ήρθε', 'pending'],
        ];
        [$statusLabel, $statusKind] = $statusMap[$status] ?? [$status, 'pending'];

        return [
            'time' => $reservation->getDateTime()->format('H:i'),
            'name' => $reservation->getName(),
            'people' => $reservation->getAdults() + $reservation->getMinors(),
            'tables' => $tableNames ? implode('–', $tableNames) : '—',
            'statusLabel' => $statusLabel,
            'statusKind' => $statusKind,
        ];
    }
}
