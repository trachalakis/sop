<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Datetime;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\ReservationsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Application\Settings\Settings;

final class Homepage
{
    public function __construct(
    	private OrdersRepository $ordersRepository,
        private ReservationsRepository $reservationsRepository,
        private Twig $twig
    ) { }

    public function __invoke(Request $request, Response $response)
    {
        $openOrders = $this->ordersRepository->findActiveTableOrders();
        $activeTakeOutOrders = $this->ordersRepository->findActiveTakeOutOrders();
    	$todaysReservations = $this->reservationsRepository->findByDate(new Datetime);

        return $this->twig->render(
        	$response,
        	'admin/homepage.twig',
        	[
        		'openOrders' => $openOrders,
                'activeTakeOutOrders' => $activeTakeOutOrders,
        		'todaysReservations' => $todaysReservations,
        	]
        );
    }
}