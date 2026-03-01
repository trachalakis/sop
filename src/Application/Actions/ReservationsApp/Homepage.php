<?php

declare(strict_types=1);

namespace Application\Actions\ReservationsApp;

use Domain\Repositories\ReservationsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Homepage
{
    private ReservationsRepository $reservationsRepository;

    private Twig $twig;

    public function __construct(Twig $twig, ReservationsRepository $reservationsRepository)
    {
        $this->twig = $twig;

        $this->reservationsRepository = $reservationsRepository;
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


        if (!isset($queryParams['all'])) {
            $reservations = array_filter($reservations, function($reservation) {
                return $reservation->getDateTime()->format('U') - (new \Datetime())->format('U') >= - 3200;
            });
        }

        $coverCount = array_reduce($reservations, function($carry, $reservation) {
            $carry += $reservation->getAdults() + $reservation->getMinors();
            return $carry;
        }, 0);



        return $this->twig->render($response, 'reservations_app/homepage.twig', [
            'reservations' => $reservations,
            'coverCount' => $coverCount,
            'showAll' => isset($queryParams['all']),
            'date' => $date,
            'prev' => (clone $date)->sub(new \DateInterval('P1D')),
            'next' => (clone $date)->add(new \DateInterval('P1D'))
        ]);
    }
}