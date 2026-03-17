<?php

declare(strict_types=1);

namespace Application\Actions\OrdersApp;

use Domain\Repositories\OrdersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class PrintOrderReceipt
{
    private OrdersRepository $ordersRepository;

    private Twig $twig;

    public function __construct(
    	OrdersRepository $ordersRepository,
    	Twig $twig
    ) {
    	$this->ordersRepository = $ordersRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$order = $this->ordersRepository->find($request->getQueryParams()['id']);

    	if ($order->getStatus() != 'OPEN') {
    		return $response->withHeader('Location', '/orders-app/')->withStatus(302);
    	}

    	return $this->twig->render($response, 'orders_app/print_order_receipt.twig');
    }
}