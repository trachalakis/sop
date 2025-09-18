<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\ReservationsRepositoryInterface;
use Domain\Repositories\TablesRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateReservation
{
	private Twig $twig;

    private ReservationsRepositoryInterface $reservationsRepository;
    
    private TablesRepositoryInterface $tablesRepository;

    public function __construct(
        ReservationsRepositoryInterface $reservationsRepository,
        TablesRepositoryInterface $tablesRepository,
        Twig $twig
    ) {
        $this->reservationsRepository = $reservationsRepository;
        $this->tablesRepository = $tablesRepository;
        $this->twig = $twig;
    }

	public function __invoke(Request $request, Response $response)
	{
		$reservation = $this->reservationsRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		if ($request->getMethod() == 'POST') {
			$reservationData = $request->getParsedBody();

            $reservation->setStatus($reservationData['status']);
            $reservation->setDateTime(new \Datetime($reservationData['dateTime']));
            $reservation->setName($reservationData['name']);
            $reservation->setAdults(intval($reservationData['adults']));
            $reservation->setMinors(intval($reservationData['minors']));
            $reservation->setTelephoneNumber($reservationData['telephoneNumber']);
            $reservation->setEmailAddress($reservationData['emailAddress']);
            $reservation->setMessage($reservationData['message']);

			$this->reservationsRepository->persist($reservation);

            return $response->withHeader('Location', '/admin/reservations')->withStatus(302);
    	}

        $tables = $this->tablesRepository->findAllBy(['active' => true, 'name' => 'asc']);

		return $this->twig->render(
            $response, 
            'admin/update_reservation.twig', 
            [
                'reservation' => $reservation,
                'tables' => $tables
            ]
        );
	}
}