<?php

declare(strict_types=1);

namespace Application\Actions\ReservationsApp;

use Domain\Repositories\ReservationsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class ToggleTableLock
{
    public function __construct(private Twig $twig, private ReservationsRepository $reservationsRepository)
    {
    }

    public function __invoke(Request $request, Response $response)
    {
       
        $reservation = $this->reservationsRepository->findOneById($request->getQueryParams()['reservationId']);
       
        $reservation->setIsTableLocked(!$reservation->getIsTableLocked());

        $this->reservationsRepository->persist($reservation);

        return $response->withHeader('Location', '/reservations-app/')->withStatus(302);
        
        
    }
}