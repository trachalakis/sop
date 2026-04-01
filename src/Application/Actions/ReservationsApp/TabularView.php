<?php

declare(strict_types=1);

namespace Application\Actions\ReservationsApp;

use Domain\Repositories\ReservationsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class TabularView
{
    public function __construct(private Twig $twig, private ReservationsRepository $reservationsRepository)
    {
    }

    public function __invoke(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        if (empty($queryParams['date'])) {
            $date = new \Datetime();
        } else {
            $date = new \Datetime($queryParams['date']);
        }

        $reservations = $this->reservationsRepository->findByDate($date);
        $tables = [];

        $coverCount = array_reduce($reservations, function($carry, $reservation) {
            $carry += $reservation->getAdults() + $reservation->getMinors();
            return $carry;
        }, 0);

        return $this->twig->render($response, 'reservations_app/tabular_view.twig', [
            'reservations' => $reservations,
            'tables' => $tables,
            'coverCount' => $coverCount,
            'date' => $date,
            'prev' => (clone $date)->sub(new \DateInterval('P1D')),
            'next' => (clone $date)->add(new \DateInterval('P1D'))
        ]);
    }
}