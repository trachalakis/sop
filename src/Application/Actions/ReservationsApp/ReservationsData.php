<?php

declare(strict_types=1);

namespace Application\Actions\ReservationsApp;

use Domain\Repositories\ReservationsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ReservationsData
{
    public function __construct(
        private ReservationsRepository $reservationsRepository,
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        $date = empty($queryParams['date'])
            ? new \Datetime()
            : new \Datetime($queryParams['date']);

        $reservations = $this->reservationsRepository->findByDate($date);

        $data = array_values(array_map(function ($r) {
            return [
                'id'              => $r->getId(),
                'name'            => $r->getName(),
                'adults'          => $r->getAdults(),
                'minors'          => $r->getMinors(),
                'dateTime'        => $r->getDateTime()->format('Y-m-d H:i:s'),
                'comments'        => $r->getComments() ?? '',
                'status'          => $r->getStatus(),
                'tables'          => $r->getTables() ?? [],
                'telephoneNumber' => $r->getTelephoneNumber() ?? '',
                'isTableLocked'   => $r->getIsTableLocked(),
            ];
        }, $reservations));

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
