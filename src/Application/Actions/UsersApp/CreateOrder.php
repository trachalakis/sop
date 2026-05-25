<?php

declare(strict_types=1);

namespace Application\Actions\UsersApp;

use DateTimeImmutable;
use Domain\Entities\Order;
use Domain\Entities\OrderEntry;
use Domain\Entities\OrderEntryExtra;
use Domain\Entities\OrderEntryGroup;
use Domain\Entities\EcrJob;
use Domain\Repositories\EcrJobsRepository;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Slim\Views\Twig;

final class CreateOrder
{
    public function __construct(
        private Twig $twig,
        private EcrJobsRepository $ecrJobsRepository,
        private MenuItemsRepository $menuItemsRepository,
        private OrdersRepository $ordersRepository,
        private UsersRepository $usersRepository
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        if ($request->getMethod() === 'POST') {
            $requestData = json_decode(file_get_contents('php://input'), true);

            $employee = $this->usersRepository->find($_SESSION['user']->getId());
            $now = new DateTimeImmutable();

            $order = new Order();
            $order->setUuid(Uuid::uuid4()->toString());
            $order->setTable(null);
            $order->setAdults(0);
            $order->setMinors(0);
            $order->setNotes('');
            $order->setTicketNumber($this->ordersRepository->getNextTicketNumber($now));
            $order->setCreatedAt($now);
            $order->setWaiter(null);
            $order->setEmployee($employee);
            $order->setReservation(null);
            $order->setStatus('CLOSED');
            $order->setPaidAt(new DatetimeImmutable);

            $orderEntryGroup = new OrderEntryGroup();
            $orderEntryGroup->setCreatedAt($now);
            $orderEntryGroup->setNotes($requestData['notes']);
            $orderEntryGroup->setOrder($order);

            $orderEntries = [];
            foreach ($requestData['orderEntries'] as $entry) {
                $menuItem = $this->menuItemsRepository->find($entry['menuItem']['id']);

                if ($menuItem->getTrackAvailableQuantity()) {
                    $menuItem->setAvailableQuantity($menuItem->getAvailableQuantity() - intval($entry['quantity']));
                    $this->menuItemsRepository->persist($menuItem);
                }

                $orderEntry = new OrderEntry();
                $orderEntry->setDiscount(0);
                $orderEntry->setOrder($order);
                $orderEntry->setMenuItem($menuItem);
                $orderEntry->setMenuItemPrice($menuItem->getPrice());
                $orderEntry->setQuantity(intval($entry['quantity']));
                $orderEntry->setFamily(1);
                $orderEntry->setTiming(intval($entry['timing'] ?? 1));
                $orderEntry->setNotes($entry['notes'] ?? '');
                $orderEntry->setIsPaid(!empty($requestData['markAsPaid']));
                $orderEntry->setOrderEntryGroup($orderEntryGroup);
                $orderEntry->setWeight(isset($entry['weight']) ? intval($entry['weight']) : null);

                $orderEntryExtras = [];
                foreach ($entry['orderEntryExtras'] as $extra) {
                    $orderEntryExtra = new OrderEntryExtra();
                    $orderEntryExtra->setName($extra['name']);
                    $orderEntryExtra->setPrice(floatval($extra['price']));
                    $orderEntryExtra->setOrderEntry($orderEntry);
                    $orderEntryExtras[] = $orderEntryExtra;
                }
                $orderEntry->setOrderEntryExtras($orderEntryExtras);

                $orderEntries[] = $orderEntry;
            }

            $order->setOrderEntries($orderEntries);
            $order->setOrderEntryGroups([$orderEntryGroup]);

            $this->ordersRepository->persist($order);

            $response->getBody()->write('ok');
            return $response;
        }

        return $this->twig->render($response, 'users_app/create_order.twig');
    }
}
