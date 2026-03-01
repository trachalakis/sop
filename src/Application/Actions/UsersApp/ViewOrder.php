<?php

declare(strict_types=1);

namespace Application\Actions\UsersApp;

use Datetime;
use Domain\Entities\Order;
use Domain\Entities\OrderEntry;
use Domain\Entities\OrderEntryExtra;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\OrderEntriesRepository;
use Domain\Repositories\TablesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class ViewOrder
{
    private Twig $twig;

    private MenuItemsRepository $menuItemsRepository;

    private OrdersRepository $ordersRepository;

    private OrderEntriesRepository $orderEntriesRepository;

    private TablesRepository $tablesRepository;

    public function __construct(
        MenuItemsRepository $menuItemsRepository,
        OrdersRepository $ordersRepository,
        OrderEntriesRepository $orderEntriesRepository,
        TablesRepository $tablesRepository,
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