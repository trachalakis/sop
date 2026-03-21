<?php

declare(strict_types=1);

namespace Application\Actions\ReservationsApp;

use Domain\Repositories\ReservationsRepository;
use Domain\Repositories\TablesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class HomepageAlt
{
    public function __construct(
        private Twig $twig,
        private ReservationsRepository $reservationsRepository,
        private TablesRepository $tablesRepository
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        $date = empty($queryParams['date'])
            ? new \Datetime()
            : new \Datetime($queryParams['date']);

        $reservations = $this->reservationsRepository->findByDate($date);
        $tables = $this->tablesRepository->findBy(['isActive' => true], ['position' => 'asc']);

        $reservationsData = array_values(array_map(function ($r) {
            return [
                'id'       => $r->getId(),
                'name'     => $r->getName(),
                'adults'   => $r->getAdults(),
                'minors'   => $r->getMinors(),
                'dateTime' => $r->getDateTime()->format('Y-m-d H:i:s'),
                'comments' => $r->getComments() ?? '',
                'status'   => $r->getStatus(),
                'tables'   => $r->getTables() ?? [],
            ];
        }, $reservations));

        $tablesData = array_values(array_map(function ($t) {
            return ['id' => $t->getId(), 'name' => $t->getName()];
        }, $tables));

        return $this->twig->render($response, 'reservations_app/homepage_alt.twig', [
            'reservationsJson' => json_encode($reservationsData),
            'tablesJson'       => json_encode($tablesData),
            'date'             => $date,
            'prev'             => (clone $date)->sub(new \DateInterval('P1D')),
            'next'             => (clone $date)->add(new \DateInterval('P1D')),
        ]);
    }
}
