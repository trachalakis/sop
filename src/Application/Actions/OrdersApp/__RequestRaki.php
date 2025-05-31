<?php

declare(strict_types=1);

namespace Application\Actions\OrdersApp;

use Domain\Repositories\OrdersRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class RequestRaki
{
	private OrdersRepositoryInterface $ordersRepository;

    private Twig $twig;

    public function __construct(
        OrdersRepositoryInterface $ordersRepository,
        Twig $twig
    ) {
        $this->ordersRepository = $ordersRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$id = $request->getQueryParams()['id'];
    	$order = $this->ordersRepository->findOneBy(['id' => $id]);

    	if ($order->getStatus() != 'OPEN') {
    		return $response->withHeader('Location', '/orders-app/')->withStatus(302);
    	}

    	return $this->twig->render($response, 'orders_app/request_raki.twig', ['orderId' => $id]);
    }
}