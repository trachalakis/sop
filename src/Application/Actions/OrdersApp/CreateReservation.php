<?php

declare(strict_types=1);

namespace Application\Actions\OrdersApp;

use Domain\Entities\Reservation;
use Domain\Repositories\ReservationsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateReservation
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
    	if ($request->getMethod() == 'POST') {
            $requestData = $request->getParsedBody();

            $reservation = new Reservation;
            $reservation->setEmailAddress(null);
            $reservation->setCreatedAt(new \Datetime);
            $reservation->setStatus('CONFIRMED');
            $reservation->setDateTime(new \Datetime($requestData['dateTime']));
            $reservation->setName($requestData['name']);
            $reservation->setAdults(intval($requestData['adults']));
            $reservation->setMinors(intval($requestData['children']));
            $reservation->setTelephoneNumber($requestData['telephoneNumber']);
            $reservation->setComments($requestData['comments']);

            $this->reservationsRepository->persist($reservation);

            return $response->withHeader('Location', '/orders-app/')->withStatus(302);
    	}

        return $this->twig->render($response, 'orders_app/create_reservation.twig');
    }
}