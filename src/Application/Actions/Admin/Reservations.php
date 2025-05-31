<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Datetime;
use Domain\Repositories\ReservationsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Reservations
{
    private $twig;

    private $reservationsRepository;

    public function __construct(
        Twig $twig,
        ReservationsRepositoryInterface $reservationsRepository
    ) {
        $this->twig = $twig;
        $this->reservationsRepository = $reservationsRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        if (empty($queryParams['date'])) {
            $date = new Datetime;
        } else {
            $date = new Datetime($queryParams['date']);
        }

        $reservations = $this->reservationsRepository->findByDate($date);

        return $this->twig->render(
        	$response,
        	'admin/reservations.twig',
        	[
        		'reservations' => $reservations,
        		'date' => $date,
        		'prev' => (clone $date)->sub(new \DateInterval('P1D')),
				'next' => (clone $date)->add(new \DateInterval('P1D'))
        	]
        );
    }
}