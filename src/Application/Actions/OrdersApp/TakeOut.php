<?php

declare(strict_types=1);

namespace Application\Actions\OrdersApp;

use Datetime;
use Domain\Entities\Order;
use Domain\Entities\OrderEntryGroup;
use Domain\Entities\OrderEntry;
use Domain\Entities\OrderEntryExtra;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\ReservationsRepository;
use Domain\Repositories\TablesRepository;
use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Slim\Views\Twig;

final class TakeOut
{
    public function __construct(
        private Twig $twig,
        private MenuItemsRepository $menuItemsRepository,
        private OrdersRepository $ordersRepository,
        private ReservationsRepository $reservationsRepository,
        private TablesRepository $tablesRepository,
        private UsersRepository $usersRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
    	if ($request->getMethod() == 'POST') {
    		$requestData = json_decode(file_get_contents("php://input"), true);

    		$waiter = $this->usersRepository->find($_SESSION['user']->getId());
            $datetime = new Datetime;

            $order = new Order;
            $order->setUuid(Uuid::uuid4()->toString());
            $order->setStatus('OPEN');
            $order->setTable(null);
            $order->setAdults(intval($requestData['adults']));
            $order->setMinors(intval($requestData['minors']));
            //$order->setNotes($requestData['notes']);
            $order->setCreatedAt($datetime);
            $order->setWaiter($waiter);
            $order->setEmployee(null);
            $order->setReservation(null);
            $order->setPaidAt($datetime);

            $orderEntryGroup = new OrderEntryGroup;
            $orderEntryGroup->setCreatedAt($datetime);
            $orderEntryGroup->setNotes('');
            $orderEntryGroup->setOrder($order);

            $orderEntries = [];
            foreach($requestData['orderEntries'] as $entry) {
            	$menuItem = $this->menuItemsRepository->find($entry['menuItem']['id']);

            	if ($menuItem->getTrackAvailableQuantity()) {
                    $menuItem->setAvailableQuantity($menuItem->getAvailableQuantity() - intval($entry['quantity']));
                    $this->menuItemsRepository->persist($menuItem);
                }

                $orderEntry = new OrderEntry;
            	$orderEntry->setDiscount(0); //TODO revisit this
            	$orderEntry->setOrder($order);
            	$orderEntry->setMenuItem($menuItem);
                $orderEntry->setMenuItemPrice($menuItem->getPrice());
            	$orderEntry->setQuantity(intval($entry['quantity']));
            	$orderEntry->setFamily(1);
            	$orderEntry->setTiming(1);
            	$orderEntry->setNotes($entry['notes']);
            	$orderEntry->setIsPaid(false);
                $orderEntry->setOrderEntryGroup($orderEntryGroup);
            	$orderEntry->setWeight(null);

            	$orderEntryExtras = [];
            	foreach($entry['orderEntryExtras'] as $extra) {
            		$orderEntryExtra = new OrderEntryExtra;
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

        return $this->twig->render($response, 'orders_app/take_out.twig');
    }
}