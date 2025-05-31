<?php

declare(strict_types=1);

namespace Application\Actions\OrdersApp;

use Datetime;
use Domain\Repositories\OrdersRepositoryInterface;
use Domain\Repositories\OrderEntriesRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class OrderPayment
{
	private OrdersRepositoryInterface $ordersRepository;

	private OrderEntriesRepositoryInterface $orderEntriesRepository;

	private Twig $twig;

	public function __construct(
    	OrdersRepositoryInterface $ordersRepository,
    	OrderEntriesRepositoryInterface $orderEntriesRepository,
    	Twig $twig
    ) {
        $this->ordersRepository = $ordersRepository;
        $this->orderEntriesRepository = $orderEntriesRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
	{
		if ($request->getMethod() == 'GET') {
			$order = $this->ordersRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

	    	if ($order->getStatus() != 'OPEN') {
	    		return $response->withHeader('Location', '/orders-app/')->withStatus(302);
	    	}
	    }

		if ($request->getMethod() == 'POST') {
    		$requestData = json_decode(file_get_contents("php://input"), true);

    		$order = $this->ordersRepository->findOneBy(['id' => $requestData['orderId']]);

    		foreach($requestData['orderEntries'] as $orderEntry) {
    			$orderEntry = $this->orderEntriesRepository->findOneBy(['id' => $orderEntry['id']]);

    			$orderEntry->setIsPaid(true);
    			//$orderEntry->setPaymentMethod(null);

    			$this->orderEntriesRepository->persist($orderEntry);
    		}

	    	if ($order->IsPaid()) {
    			$order->setStatus('PAID');
    			$order->setPaidAt(new Datetime);
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