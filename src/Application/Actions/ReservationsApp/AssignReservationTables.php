<?php

declare(strict_types=1);

namespace Application\Actions\ReservationsApp;

use Domain\Repositories\ReservationsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AssignReservationTables
{
    public function __construct(
        private ReservationsRepository $reservationsRepository
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $body = json_decode((string) $request->getBody(), true);
        $reservation = $this->reservationsRepository->find((int) $body['id']);
        $reservation->setTables($body['tables'] ?? []);
        $this->reservationsRepository->persist($reservation);

        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
