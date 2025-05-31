<?php

declare(strict_types=1);

namespace Application\Actions\OrdersApp;

use Datetime;
use Domain\Entities\Order;
use Domain\Entities\OrderEntryGroup;
use Domain\Entities\OrderEntry;
use Domain\Entities\OrderEntryExtra;
use Domain\Repositories\MenuItemsRepositoryInterface;
use Domain\Repositories\OrdersRepositoryInterface;
use Domain\Repositories\ReservationsRepositoryInterface;
use Domain\Repositories\TablesRepositoryInterface;
use Domain\Repositories\UsersRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Slim\Views\Twig;

final class TakeAway
{
    private Twig $twig;

    private MenuItemsRepositoryInterface $menuItemsRepository;

    private OrdersRepositoryInterface $ordersRepository;

    private ReservationsRepositoryInterface $reservationsRepository;

    private TablesRepositoryInterface $tablesRepository;

    private UsersRepositoryInterface $usersRepository;

    public function __construct(
        Twig $twig,
        MenuItemsRepositoryInterface $menuItemsRepository,
        OrdersRepositoryInterface $ordersRepository,
        ReservationsRepositoryInterface $reservationsRepository,
        TablesRepositoryInterface $tablesRepository,
        UsersRepositoryInterface $usersRepository
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
            $datetime = new Datetime;

            $order = new Order;
            $order->setUuid(Uuid::uuid4()->toString());
            $order->setStatus('PAID');
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
            	$menuItem = $this->menuItemsRepository->findOneBy(['id' => $entry['menuItem']['id']]);

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
            	$orderEntry->setIsPaid(true);
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

        return $this->twig->render($response, 'orders_app/take_away.twig');
    }
}