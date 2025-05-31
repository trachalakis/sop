<?php

declare(strict_types=1);

namespace Application\Actions\OrdersApp;

use Domain\Repositories\ReservationsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class PrintReservations
{
	private ReservationsRepositoryInterface $reservationsRepository;

    private Twig $twig;

    public function __construct(
        ReservationsRepositoryInterface $reservationsRepository,
        Twig $twig
    ) {
        $this->reservationsRepository = $reservationsRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	//$todaysReservations = $this->reservationsRepository->findByDate(new Datetime);

    	return $this->twig->render($response, 'orders_app/print_reservations.twig');
    }
}