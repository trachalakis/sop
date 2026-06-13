<?php

declare(strict_types=1);

namespace Application\Services;

use DateTimeImmutable;
use Domain\Entities\EcrJob;
use Domain\Entities\Order;
use Domain\Entities\OrderEntry;
use Domain\Entities\OrderEntryExtra;
use Domain\Entities\OrderEntryGroup;
use Domain\Entities\User;
use Domain\Repositories\EcrJobsRepository;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\OrdersRepository;
use Ramsey\Uuid\Uuid;

final class TakeOutOrderFactory
{
    public function __construct(
        private EcrJobsRepository $ecrJobsRepository,
        private MenuItemsRepository $menuItemsRepository,
        private OrdersRepository $ordersRepository
    ) {}

    /**
     * @param list<array{
     *     menuItem: \Domain\Entities\MenuItem,
     *     menuItemPrice: float,
     *     quantity: int,
     *     timing: int,
     *     notes: string,
     *     weight: ?int,
     *     extras: list<array{name: string, price: float}>
     * }> $entrySpecs
     */
    public function create(array $entrySpecs, string $groupNotes, User $waiter, bool $markAsPaid): Order
    {
        $now = new DateTimeImmutable();

        $order = new Order();
        $order->setUuid(Uuid::uuid4()->toString());
        $order->setTable(null);
        $order->setAdults(0);
        $order->setMinors(0);
        $order->setNotes('');
        $order->setTicketNumber($this->ordersRepository->getNextTicketNumber($now));
        $order->setCreatedAt($now);
        $order->setWaiter($waiter);
        $order->setEmployee(null);
        $order->setReservation(null);

        if ($markAsPaid) {
            $order->setStatus('CLOSED');
            $order->setPaidAt($now);
        } else {
            $order->setStatus('OPEN');
            $order->setPaidAt(null);
        }

        $orderEntryGroup = new OrderEntryGroup();
        $orderEntryGroup->setCreatedAt($now);
        $orderEntryGroup->setNotes($groupNotes);
        $orderEntryGroup->setOrder($order);

        $orderEntries = [];
        foreach ($entrySpecs as $spec) {
            $menuItem = $spec['menuItem'];

            if ($menuItem->getTrackAvailableQuantity()) {
                $menuItem->setAvailableQuantity($menuItem->getAvailableQuantity() - $spec['quantity']);
                $this->menuItemsRepository->persist($menuItem);
            }

            $orderEntry = new OrderEntry();
            $orderEntry->setDiscount(0);
            $orderEntry->setOrder($order);
            $orderEntry->setMenuItem($menuItem);
            $orderEntry->setMenuItemPrice($spec['menuItemPrice']);
            $orderEntry->setQuantity($spec['quantity']);
            $orderEntry->setFamily(1);
            $orderEntry->setTiming($spec['timing']);
            $orderEntry->setNotes($spec['notes']);
            $orderEntry->setIsPaid($markAsPaid);
            $orderEntry->setOrderEntryGroup($orderEntryGroup);
            $orderEntry->setWeight($spec['weight']);

            $orderEntryExtras = [];
            foreach ($spec['extras'] as $extra) {
                $orderEntryExtra = new OrderEntryExtra();
                $orderEntryExtra->setName($extra['name']);
                $orderEntryExtra->setPrice($extra['price']);
                $orderEntryExtra->setOrderEntry($orderEntry);
                $orderEntryExtras[] = $orderEntryExtra;
            }
            $orderEntry->setOrderEntryExtras($orderEntryExtras);

            $orderEntries[] = $orderEntry;
        }

        $order->setOrderEntries($orderEntries);
        $order->setOrderEntryGroups([$orderEntryGroup]);

        $this->ordersRepository->persist($order);

        $ecrJob = new EcrJob();
        $ecrJob->setOrder($order);
        $ecrJob->setStatus('pending');
        $ecrJob->setAttempts(0);
        $ecrJob->setCreatedAt(new DateTimeImmutable());
        $this->ecrJobsRepository->persist($ecrJob);

        return $order;
    }
}
