<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\OrdersRepositoryInterface;
use Domain\Entities\User;
use Domain\Repositories\UsersRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UserOrders
{
	private $twig;

    private $ordersRepository;

    private $usersRepository;

    public function __construct(OrdersRepositoryInterface $ordersRepository, Twig $twig, UsersRepositoryInterface $usersRepository)
    {
        $this->twig = $twig;
        $this->usersRepository = $usersRepository;
        $this->ordersRepository = $ordersRepository;
    }

	public function __invoke(Request $request, Response $response)
	{
		$user = $this->usersRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

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