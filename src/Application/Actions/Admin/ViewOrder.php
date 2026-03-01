<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\OrdersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class ViewOrder
{
    private Twig $twig;

    private OrdersRepository $ordersRepository;

    public function __construct(
        OrdersRepository $ordersRepository,
        Twig $twig
    ) {
        $this->ordersRepository = $ordersRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$id = $request->getQueryParams()['id'];

    	/*if ($request->getMethod() == 'POST') {
    		$requestData = json_decode(file_get_contents("php://input"), true);

    		$table = $this->tablesRepository->findOneBy(['id' => $requestData['table']['id']]);
    		$order = $this->ordersRepository->findOneBy(['id' => $requestData['id']]);

            $order->setTable($table);
            $order->setAdults(intval($requestData['adults']));
            $order->setMinors(intval($requestData['minors']));
            $order->setNotes($requestData['notes']);

            $orderEntries = [];
            foreach($requestData['orderEntries'] as $entry) {
            	$menuItem = $this->menuItemsRepository->findoneBy(['id' => $entry['menuItem']['id']]);

            	$orderEntry = new OrderEntry;
                $orderEntry->setCreatedAt(new Datetime);
            	$orderEntry->setDiscount(0); //TODO revisit this
            	$orderEntry->setOrder($order);
            	$orderEntry->setMenuItem($menuItem);
            	$orderEntry->setQuantity(intval($entry['quantity']));
            	$orderEntry->setPrice(floatval($entry['price']));
            	$orderEntry->setFamily(intval($entry['family']));
            	$orderEntry->setTiming(intval($entry['timing']));
            	$orderEntry->setNotes($entry['notes']);
            	$orderEntry->setPaymentMethod(null);
            	$orderEntry->setIsPaid(false);
            	$orderEntry->setIsDeleted(false);

            	if ($menuItem->getIsPricePerKg()) {
            		$orderEntry->setWeight(intval($entry['weight']));
            	} else {
            		$orderEntry->setWeight(null);
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

            $this->ordersRepository->persist($order);

			$response->getBody()->write('ok');
			return $response;
    	}*/
        $order = $this->ordersRepository->findOneBy(['id' => $request->getQueryParams()['id']]);
    	return $this->twig->render(
            $response,
            'admin/view_order.twig',
            [
                'order' => $order
            ]
        );
    }
}