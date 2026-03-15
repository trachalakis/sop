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

final class CreateOrder
{
    private Twig $twig;

    private MenuItemsRepository $menuItemsRepository;

    private OrdersRepository $ordersRepository;

    private ReservationsRepository $reservationsRepository;

    private TablesRepository $tablesRepository;

    private UsersRepository $usersRepository;

    public function __construct(
        Twig $twig,
        MenuItemsRepository $menuItemsRepository,
        OrdersRepository $ordersRepository,
        ReservationsRepository $reservationsRepository,
        TablesRepository $tablesRepository,
        UsersRepository $usersRepository
    ) {
        $this->twig = $twig;
        $this->menuItemsRepository = $menuItemsRepository;
        $this->ordersRepository = $ordersRepository;
        $this->reservationsRepository = $reservationsRepository;
        $this->tablesRepository = $tablesRepository;
        $this->usersRepository = $usersRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
    	if ($request->getMethod() == 'POST') {
    		$requestData = json_decode(file_get_contents("php://input"), true);

    		$waiter = $this->usersRepository->findOneBy(['id' => $_SESSION['user']->getId()]);

    		$table = null;
    		if ($requestData['table'] != null) {
    			$table = $this->tablesRepository->findOneBy(['id' => $requestData['table']['id']]);
    		}
    		$employee = null;
    		if ($requestData['employee'] != null) {
    			$employee = $this->usersRepository->findOneBy(['id' => $requestData['employee']['id']]);
    		}
            $reservation = null;
            if ($requestData['reservationId'] != null) {
                $reservation = $this->reservationsRepository->findOneBy(['id' => $requestData['reservationId']]);
            }

            $datetime = new Datetime;

            //$orderIsPaid = $employee != null || $table->getName() == 'Take away';

            $order = new Order;
            $order->setUuid(Uuid::uuid4()->toString());
            $order->setStatus($employee != null ? 'PAID' : 'OPEN'); //todo create separate action for user orders
            $order->setTable($table);
            $order->setAdults(intval($requestData['adults']));
            $order->setMinors(intval($requestData['minors']));
            //$order->setNotes($requestData['notes']);
            $order->setCreatedAt($datetime);
            $order->setWaiter($waiter);
            $order->setEmployee($employee);
            $order->setReservation($reservation);
            $order->setPaidAt($employee == null ? null : $datetime);

            $orderEntryGroup = new OrderEntryGroup;
            $orderEntryGroup->setCreatedAt($datetime);
            $orderEntryGroup->setNotes($requestData['notes']);
            $orderEntryGroup->setOrder($order);

            $orderEntries = [];
            foreach($requestData['orderEntries'] as $entry) {
            	$menuItem = $this->menuItemsRepository->findOneBy(['id' => $entry['menuItem']['id']]);

            	if ($menuItem->getTrackAvailableQuantity()) {
                    $menuItem->setAvailableQuantity($menuItem->getAvailableQuantity() - intval($entry['quantity']));
                    $this->menuItemsRepository->persist($menuItem);

                    //TODO
                    if (function_exists('apcu_clear_cache')) {
                        apcu_clear_cache();
                    }
                }

                $orderEntry = new OrderEntry;
                //$orderEntry->setCreatedAt($datetime);
            	$orderEntry->setDiscount(0); //TODO revisit this
            	$orderEntry->setOrder($order);
            	$orderEntry->setMenuItem($menuItem);
                $orderEntry->setMenuItemPrice($menuItem->getPrice());
            	$orderEntry->setQuantity(intval($entry['quantity']));
            	//$orderEntry->setMaxQuantity(intval($entry['quantity']));
            	//$orderEntry->setPrice(floatval($entry['price']));
            	$orderEntry->setFamily(intval($entry['family']));
            	$orderEntry->setTiming(intval($entry['timing']));
            	$orderEntry->setNotes($entry['notes']);
            	//$orderEntry->setPaymentMethod(null);
            	$orderEntry->setIsPaid(false);
                $orderEntry->setOrderEntryGroup($orderEntryGroup);

            	if ($menuItem->getPriceUnit() == 'kg') {
            		$orderEntry->setWeight(intval($entry['weight']));
            		//$orderEntry->setMaxWeight(intval($entry['weight']));
            	} else {
            		$orderEntry->setWeight(null);
            		//$orderEntry->setMaxWeight(null);
            	}

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

        return $this->twig->render($response, 'orders_app/create_order.twig');
    }
}