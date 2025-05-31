<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Reservation;
use Domain\Repositories\ReservationsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateReservation
{
	private $twig;

    private $reservationsRepository;

    public function __construct(Twig $twig, ReservationsRepositoryInterface $reservationsRepository)
    {
        $this->twig = $twig;
        $this->reservationsRepository = $reservationsRepository;
    }

    public function __invoke(Request $request, Response $response)
	{
		if ($request->getMethod() == 'POST') {
            $reservationData = $request->getParsedBody();

            $reservation = new Reservation;
            $reservation->setCreatedAt(new \Datetime);
            $reservation->setStatus($reservationData['status']);
            $reservation->setDateTime(new \Datetime($reservationData['dateTime']));
            $reservation->setName($reservationData['name']);
            $reservation->setAdults(intval($reservationData['adults']));
            $reservation->setMinors(intval($reservationData['children']));
            $reservation->setTelephoneNumber($reservationData['telephoneNumber']);
            $reservation->setEmailAddress($reservationData['emailAddress']);
            $reservation->setMessage($reservationData['message']);

            $this->reservationsRepository->persist($reservation);

            return $response->withHeader('Location', '/admin/reservations')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/create_reservation.twig');
	}
}