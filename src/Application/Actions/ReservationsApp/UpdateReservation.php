<?php

declare(strict_types=1);

namespace Application\Actions\ReservationsApp;

use Domain\Repositories\ReservationsRepository;
use Domain\Repositories\TablesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateReservation
{
    public function __construct(
        private ReservationsRepository $reservationsRepository,
        private TablesRepository $tablesRepository,
        private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $reservation = $this->reservationsRepository->find($request->getQueryParams()['id']);

        if ($request->getMethod() == 'POST') {
            $requestData = $request->getParsedBody();
            
            $reservation->setDateTime(new \Datetime(sprintf("%s %s:00", $requestData['date'], $requestData['time'])));
            $reservation->setName(mb_strtoupper($requestData['name']));
            $reservation->setAdults(intval($requestData['adults']));
            $reservation->setMinors(intval($requestData['minors']));
            $reservation->setTelephoneNumber($requestData['telephoneNumber']);
            $reservation->setComments(mb_strtoupper($requestData['comments']));
            $reservation->setTables($requestData['tables'] ?? []);

            $this->reservationsRepository->persist($reservation);

            return $response->withHeader('Location', '/reservations-app/')->withStatus(302);
    	}

        $tables = $this->tablesRepository->findBy(['isActive' => true], ['name' => 'asc']);

        return $this->twig->render(
            $response,
            'reservations_app/update_reservation.twig',
            [
                'reservation' => $reservation,
                'tables' => $tables
            ]
        );
    }
}