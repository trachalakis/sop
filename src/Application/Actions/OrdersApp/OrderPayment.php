<?php

declare(strict_types=1);

namespace Application\Actions\OrdersApp;

use DateTimeImmutable;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\OrderEntriesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class OrderPayment
{
	public function __construct(
    	private OrdersRepository $ordersRepository,
    	private OrderEntriesRepository $orderEntriesRepository,
    	private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
	{
		if ($request->getMethod() == 'GET') {
			$order = $this->ordersRepository->find($request->getQueryParams()['id']);

	    	if ($order->getStatus() != 'OPEN') {
	    		return $response->withHeader('Location', '/orders-app/')->withStatus(302);
	    	}
	    }

		if ($request->getMethod() == 'POST') {
    		$requestData = json_decode(file_get_contents("php://input"), true);

    		$order = $this->ordersRepository->find($requestData['orderId']);

    		foreach($requestData['orderEntries'] as $orderEntry) {
    			$orderEntry = $this->orderEntriesRepository->find($orderEntry['id']);

    			$orderEntry->setIsPaid(true);

    			$this->orderEntriesRepository->persist($orderEntry);
    		}

	    	if ($order->IsPaid()) {
    			$order->setStatus('PAID');
    			$order->setPaidAt(new DateTimeImmutable);
    			$this->ordersRepository->persist($order);
    		}

    		$response->getBody()->write('ok');
			return $response;
    	}
		return $this->twig->render(
        	$response,
        	'orders_app/order_payment.twig'
        );
	}
}