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
            $isJson = str_contains($request->getHeaderLine('Content-Type'), 'application/json');

            if ($isJson) {
                $requestData = json_decode(file_get_contents('php://input'), true);

                try {
                    $dateTime = new DateTime(sprintf("%s %s:00", $requestData['date'], $requestData['time']));
                    if ($dateTime < new DateTime) {
                        throw new InvalidDateException('Η κράτηση δεν μπορεί να είναι στο παρελθόν');
                    }

                    $reservation = new Reservation;
                    $reservation->setIsTableLocked(false);
                    $reservation->setEmailAddress(null);
                    $reservation->setCreatedAt(new DateTime);
                    $reservation->setStatus('PENDING');
                    $reservation->setDateTime($dateTime);
                    $reservation->setName(mb_strtoupper($requestData['name']));
                    $reservation->setAdults(intval($requestData['adults']));
                    $reservation->setMinors(intval($requestData['minors']));
                    $reservation->setTelephoneNumber($requestData['telephoneNumber']);
                    $reservation->setComments(mb_strtoupper($requestData['comments'] ?? ''));
                    $reservation->setTables($requestData['tables'] ?? []);

                    $this->reservationsRepository->persist($reservation);

                    $response->getBody()->write(json_encode([
                        'id'              => $reservation->getId(),
                        'name'            => $reservation->getName(),
                        'adults'          => $reservation->getAdults(),
                        'minors'          => $reservation->getMinors(),
                        'dateTime'        => $reservation->getDateTime()->format('Y-m-d H:i:s'),
                        'comments'        => $reservation->getComments() ?? '',
                        'status'          => $reservation->getStatus(),
                        'telephoneNumber' => $reservation->getTelephoneNumber() ?? '',
                        'tables'          => $reservation->getTables() ?? [],
                    ]));
                    return $response->withHeader('Content-Type', 'application/json');
                } catch (InvalidDateException $e) {
                    $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }

            try {
                $requestData = $request->getParsedBody();

                $dateTime = new DateTime(sprintf("%s %s:00", $requestData['date'], $requestData['time']));
                if ($dateTime < new DateTime) {
                    throw new InvalidDateException('Η κράτηση δεν μπορεί να είναι στο παρελθόν');
                }

                $reservation = new Reservation;
                $reservation->setIsTableLocked(false);
                $reservation->setEmailAddress(null);
                $reservation->setCreatedAt(new DateTime);
                $reservation->setStatus('PENDING');
                $reservation->setDateTime($dateTime);
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
