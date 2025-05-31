<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\ReservationsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class DeleteReservation
{
	private $twig;

    private $reservationsRepository;

    public function __construct(Twig $twig, ReservationsRepositoryInterface $reservationsRepository)
    {
        $this->reservationsRepository = $reservationsRepository;
        $this->twig = $twig;
    }

	public function __invoke(Request $request, Response $response)
	{
		$reservation = $this->reservationsRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		$this->reservationsRepository->delete($reservation);

        return $response->withHeader('Location', '/admin/reservations')->withStatus(302);
	}
}