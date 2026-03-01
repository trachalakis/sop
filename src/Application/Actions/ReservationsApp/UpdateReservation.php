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
    private ReservationsRepository $reservationsRepository;

    private TablesRepository $tablesRepository;

    private Twig $twig;

    public function __construct(
        ReservationsRepository $reservationsRepository,
        TablesRepository $tablesRepository,
        Twig $twig
    ) {
        $this->reservationsRepository = $reservationsRepository;
        $this->tablesRepository = $tablesRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	//$id = $request->getQueryParams()['id'];

        $reservation = $this->reservationsRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

        if ($request->getMethod() == 'POST') {
            $requestData = $request->getParsedBody();
            
            //dd($requestData);
            //$reservation = new Reservation;
            //$reservation->setEmailAddress(null);
            //$reservation->setCreatedAt(new \Datetime);
            //$reservation->setStatus('CONFIRMED');
            $reservation->setDateTime(new \Datetime(sprintf("%s %s:00", $requestData['date'], $requestData['time'])));
            $reservation->setName(mb_strtoupper($requestData['name']));
            $reservation->setAdults(intval($requestData['adults']));
            $reservation->setMinors(intval($requestData['minors']));
            $reservation->setTelephoneNumber($requestData['telephoneNumber']);
            $reservation->setTables($requestData['tables']);
            $reservation->setComments(mb_strtoupper($requestData['comments']));

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