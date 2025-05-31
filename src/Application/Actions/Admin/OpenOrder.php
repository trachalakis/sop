<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\OrdersRepositoryInterface;
use Domain\Repositories\OrderEntriesRepositoryInterface;
use Domain\Repositories\TablesRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class OpenOrder
{
	private OrdersRepositoryInterface $ordersRepository;

	public function __construct(
        OrdersRepositoryInterface $ordersRepository
    ) {
        $this->ordersRepository = $ordersRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$id = $request->getQueryParams()['id'];
    	$order = $this->ordersRepository->findOneBy(['id' => $id]);

    	$order->setStatus('OPEN');
    	$order->setPaidAt(null);
    	foreach ($order->getOrderEntries() as $orderEntry) {
    		$orderEntry->setIsPaid(false);
    	}

    	$this->ordersRepository->persist($order);

    	return $response->withHeader('Location', '/admin/report')->withStatus(302);
    }
}