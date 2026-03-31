<?php

declare(strict_types=1);

namespace Application\Actions\ReservationsApp;

use DateTime;
use Domain\Entities\Reservation;
use Domain\Repositories\ReservationsRepository;
use Domain\Repositories\TablesRepository;
use Application\Exceptions\InvalidDateException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateReservation
{
    public function __construct(
        private ReservationsRepository $reservationsRepository,
        private TablesRepository $tablesRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response)
    {
    	if ($request->getMethod() == 'POST') {
            try {
                $requestData = $request->getParsedBody();
                
                $dateTime = new Datetime(sprintf("%s %s:00", $requestData['date'], $requestData['time']));
                if ($dateTime < new Datetime) {
                    throw new InvalidDateException('Η κράτηση δεν μπορεί να είναι στο παρελθόν');
                }

                $reservation = new Reservation;
                $reservation->setIsTableLocked(false);
                $reservation->setEmailAddress(null);
                $reservation->setCreatedAt(new Datetime);
                $reservation->setStatus('PENDING');
                $reservation->setDateTime();
                $reservation->setName(mb_strtoupper($requestData['name']));
                $reservation->setAdults(intval($requestData['adults']));
                $reservation->setMinors(intval($requestData['minors']));
                $reservation->setTelephoneNumber($requestData['telephoneNumber']);
                $reservation->setComments(mb_strtoupper($requestData['comments']));
                $reservation->setTables([$requestData['table']] ?? []);

                $this->reservationsRepository->persist($reservation);
            
                return $response->withHeader('Location', '/reservations-app/')->withStatus(302);
            } catch (InvalidDateException $e) {
                $exception = $e;
            }
    	}

        $tables = $this->tablesRepository->findBy(['isActive' => true], ['name' => 'asc']);
        
        return $this->twig->render(
            $response,
            'reservations_app/create_reservation.twig',
            [
                'tables' => $tables,
                'exception' => $exception ?? null
            ]
        );
    }
}