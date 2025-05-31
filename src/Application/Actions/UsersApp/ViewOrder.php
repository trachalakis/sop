<?php

declare(strict_types=1);

namespace Application\Actions\UsersApp;

use Datetime;
use Domain\Entities\Order;
use Domain\Entities\OrderEntry;
use Domain\Entities\OrderEntryExtra;
use Domain\Repositories\MenuItemsRepositoryInterface;
use Domain\Repositories\OrdersRepositoryInterface;
use Domain\Repositories\OrderEntriesRepositoryInterface;
use Domain\Repositories\TablesRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class ViewOrder
{
    private Twig $twig;

    private MenuItemsRepositoryInterface $menuItemsRepository;

    private OrdersRepositoryInterface $ordersRepository;

    private OrderEntriesRepositoryInterface $orderEntriesRepository;

    private TablesRepositoryInterface $tablesRepository;

    public function __construct(
        MenuItemsRepositoryInterface $menuItemsRepository,
        OrdersRepositoryInterface $ordersRepository,
        OrderEntriesRepositoryInterface $orderEntriesRepository,
        TablesRepositoryInterface $tablesRepository,
        Twig $twig
    ) {
        $this->menuItemsRepository = $menuItemsRepository;
        $this->ordersRepository = $ordersRepository;
        $this->orderEntriesRepository = $orderEntriesRepository;
        $this->tablesRepository = $tablesRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$uuid = $request->getQueryParams()['uuid'];
    	
        $order = $this->ordersRepository->findOneBy(['uuid' => $request->getQueryParams()['uuid']]);
    	return $this->twig->render(
            $response,
            'users_app/view_order.twig',
            [
                'order' => $order
            ]
        );
    }
}