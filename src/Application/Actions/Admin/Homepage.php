<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Datetime;
use Domain\Repositories\OrdersRepositoryInterface;
use Domain\Repositories\ReservationsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Application\Settings\Settings;

final class Homepage
{
	private OrdersRepositoryInterface $ordersRepository;

	private ReservationsRepositoryInterface $reservationsRepository;

	private Twig $twig;

    //private Settings $settings;

    public function __construct(
    	OrdersRepositoryInterface $ordersRepository,
        ReservationsRepositoryInterface $reservationsRepository,
        Twig $twig
        //Settings $settings
    ) {
        $this->ordersRepository = $ordersRepository;
        $this->reservationsRepository = $reservationsRepository;
        $this->twig = $twig;
        //$this->settings = $settings;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$openOrders = $this->ordersRepository->findBy(['status' => 'OPEN'], ['createdAt' => 'desc']);

        $adults = 0;
        $minors = 0;
        foreach($openOrders as $order) {
            $adults += $order->getAdults();
            $minors += $order->getMinors();
        }

    	$todaysReservations = $this->reservationsRepository->findByDate(new Datetime);

        return $this->twig->render(
        	$response,
        	'admin/homepage.twig',
        	[
        		'openOrders' => $openOrders,
                'adults' => $adults,
                'minors' => $minors,
        		'todaysReservations' => $todaysReservations,
               // 'siteName' => $this->settings->get('siteName')
        	]
        );
    }
}