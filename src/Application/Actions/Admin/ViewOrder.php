<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\OrdersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class ViewOrder
{
    public function __construct(
        private OrdersRepository $ordersRepository,
        private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $order = $this->ordersRepository->find($request->getQueryParams()['id']);
    	return $this->twig->render(
            $response,
            'admin/view_order.twig',
            [
                'order' => $order
            ]
        );
    }
}