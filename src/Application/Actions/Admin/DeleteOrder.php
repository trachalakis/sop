<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\OrdersRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteOrder
{
    private OrdersRepositoryInterface $ordersRepository;

    public function __construct(OrdersRepositoryInterface $ordersRepository)
    {
        $this->ordersRepository = $ordersRepository;
    }

	public function __invoke(Request $request, Response $response)
	{
		$order = $this->ordersRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		$this->ordersRepository->delete($order);

        return $response->withHeader('Location', '/admin/report')->withStatus(302);
	}
}