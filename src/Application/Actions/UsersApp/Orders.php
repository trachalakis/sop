<?php

declare(strict_types=1);

namespace Application\Actions\UsersApp;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Domain\Repositories\OrdersRepository;
use Slim\Views\Twig;

final class Orders
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
        $orders = $this->ordersRepository->findBy(['employee' => $_SESSION['user']]);
        return $this->twig->render($response, 'users_app/orders.twig', [
            'orders' => $orders
        ]);
    }
}