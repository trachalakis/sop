<?php

declare(strict_types=1);

namespace Application\Actions\OrdersApp;

use Datetime;
use Domain\Entities\Order;
use Domain\Entities\OrderEntry;
use Domain\Entities\OrderEntryCancellation;
use Domain\Entities\OrderEntryGroup;
use Domain\Entities\OrderEntryExtra;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\OrderEntriesRepository;
use Domain\Repositories\OrderEntryCancellationsRepository;
use Domain\Repositories\OrderEntryGroupsRepository;
use Domain\Repositories\TablesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateOrder
{
    private Twig $twig;

    private MenuItemsRepository $menuItemsRepository;

    private OrdersRepository $ordersRepository;

    private OrderEntriesRepository $orderEntriesRepository;

    private OrderEntryCancellationsRepository $orderEntryCancellationsRepository;

    private OrderEntryGroupsRepository $orderEntryGroupsRepository;

    private TablesRepository $tablesRepository;

    public function __construct(
        MenuItemsRepository $menuItemsRepository,
        OrdersRepository $ordersRepository,
        OrderEntriesRepository $orderEntriesRepository,
        OrderEntryCancellationsRepository $orderEntryCancellationsRepository,
        OrderEntryGroupsRepository $orderEntryGroupsRepository,
        TablesRepository $tablesRepository,
        Twig $twig
    ) {
        $this->menuItemsRepository = $menuItemsRepository;
        $this->ordersRepository = $ordersRepository;
        $this->orderEntriesRepository = $orderEntriesRepository;
        $this->orderEntryCancellationsRepository = $orderEntryCancellationsRepository;
        $this->orderEntryGroupsRepository = $orderEntryGroupsRepository;
        $this->tablesRepository = $tablesRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$id = $request->getQueryParams()['id'];

    	if ($request->getMethod() == 'GET') {
	    	$order = $this->ordersRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

	    	if ($order->getStatus() != 'OPEN') {
	    		return $response->withHeader('Location', '/orders-app/')->withStatus(302);
	    	}
	    }

    	if ($request->getMethod() == 'POST') {
    		$requestData = json_decode(file_get_contents("php://input"), true);

    		$table = $this->tablesRepository->findOneBy(['id' => $requestData['order']['table']['id']]);
    		$order = $this->ordersRepository->findOneBy(['id' => $requestData['order']['id']]);

            $order->setTable($table);
            $order->setAdults(intval($requestData['order']['adults']));
            $order->setMinors(intval($requestData['order']['minors']));
            //$order->setNotes($requestData['notes']);

            $datetime = new Datetime;
            if (count($requestData['newOrderEntryGroup']['orderEntries']) > 0) {
                $orderEntryGroup = new OrderEntryGroup;
                $orderEntryGroup->setCreatedAt($datetime);
                $orderEntryGroup->setNotes($requestData['newOrderEntryGroup']['notes']);
                $orderEntryGroup->setOrder($order);

                $this->orderEntryGroupsRepository->persist($orderEntryGroup);

                foreach($requestData['newOrderEntryGroup']['orderEntries'] as $entry) {
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
                    $orderEntry->setMenuItem($menuItem);
                    $orderEntry->setMenuItemPrice($menuItem->getPrice());
                    $orderEntry->setQuantity(intval($entry['quantity']));
                    $orderEntry->setIsPaid(false);
                    if ($menuItem->getPriceUnit() == 'kg') {
                        $orderEntry->setWeight(intval($entry['weight']));
                    } else {
                        $orderEntry->setWeight(null);
                    }
                    $orderEntry->setDiscount(intval($entry['discount']));
                    $orderEntry->setOrder($order);
                    $orderEntry->setFamily(intval($entry['family']));
                    $orderEntry->setTiming(intval($entry['timing']));
                    $orderEntry->setNotes($entry['notes']);

                    $orderEntryExtras = [];
                    foreach($entry['orderEntryExtras'] as $extra) {
                        $orderEntryExtra = new OrderEntryExtra;
                        $orderEntryExtra->setName($extra['name']);
                        $orderEntryExtra->setPrice(floatval($extra['price']));
                        $orderEntryExtra->setOrderEntry($orderEntry);

                        $orderEntryExtras[] = $orderEntryExtra;
                    }
                    $orderEntry->setOrderEntryExtras($orderEntryExtras);
                    $orderEntry->setOrderEntryGroup($orderEntryGroup);

                    $this->orderEntriesRepository->persist($orderEntry);
                }
            }

            foreach($requestData['order']['orderEntryGroups'] as $orderEntryGroup) {
                foreach($orderEntryGroup['orderEntries'] as $entry) {
                    $orderEntry = $this->orderEntriesRepository->findOneBy(['id' => $entry['id']]);
                    $orderEntry->setQuantity(intval($entry['quantity']));
                    $orderEntry->setDiscount(floatval($entry['discount']));
                    $orderEntry->setDiscountReason($entry['discountReason']);

                    $this->orderEntriesRepository->persist($orderEntry);

                    foreach($entry['orderEntryCancellations'] as $entryCancellation) {
                        if (!isset($entryCancellation['id'])) {
                            $cancellation = new OrderEntryCancellation();
                            $cancellation->setCreatedAt($datetime);
                            $cancellation->setCancellationReason($entryCancellation['cancellationReason']);
                            $cancellation->setOrderEntry($orderEntry);

                            $this->orderEntryCancellationsRepository->persist($cancellation);
                        }
                    }
                }
            }
            $this->ordersRepository->persist($order);

			$response->getBody()->write('ok');
			return $response;
    	}

    	return $this->twig->render($response, 'orders_app/update_order.twig', ['orderId' => $id]);
    }
}