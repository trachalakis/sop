<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\OrdersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteOrder
{
    public function __construct(private OrdersRepository $ordersRepository)
    {
    }

	public function __invoke(Request $request, Response $response)
	{
		$order = $this->ordersRepository->find($request->getQueryParams()['id']);

		$this->ordersRepository->delete($order);

        return $response->withHeader('Location', '/admin/report')->withStatus(302);
	}
}