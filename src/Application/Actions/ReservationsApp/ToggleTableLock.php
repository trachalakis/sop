<?php

declare(strict_types=1);

namespace Application\Actions\ReservationsApp;

use Domain\Repositories\ReservationsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class ToggleTableLock
{
    private ReservationsRepositoryInterface $reservationsRepository;

    private Twig $twig;

    public function __construct(Twig $twig, ReservationsRepositoryInterface $reservationsRepository)
    {
        $this->twig = $twig;

        $this->reservationsRepository = $reservationsRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
       
        $reservation = $this->reservationsRepository->findOneById($request->getQueryParams()['reservationId']);
       
        $reservation->setIsTableLocked(!$reservation->getIsTableLocked());

        $this->reservationsRepository->persist($reservation);

        return $response->withHeader('Location', '/reservations-app/')->withStatus(302);
        
        
    }
}