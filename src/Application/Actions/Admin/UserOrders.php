<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\OrdersRepository;
use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UserOrders
{
    private OrdersRepository $ordersRepository;

    private Twig $twig;
    
    private UsersRepository $usersRepository;

    public function __construct(
        OrdersRepository $ordersRepository,
        Twig $twig,
        UsersRepository $usersRepository
    ) {
        $this->twig = $twig;
        $this->usersRepository = $usersRepository;
        $this->ordersRepository = $ordersRepository;
    }

	public function __invoke(Request $request, Response $response)
	{
		$user = $this->usersRepository->find($request->getQueryParams()['id']);

        $orders = $this->ordersRepository->findBy(['employee' => $user]);

		return $this->twig->render(
            $response,
            'admin/user_orders.twig',
            [
                'user' => $user,
                'orders' => $orders
            ]
        );
	}
}