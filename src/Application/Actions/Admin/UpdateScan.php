<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Scan;
use Domain\Repositories\ScansRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateScan
{
    public function __construct(private Twig $twig, private ScansRepository $scansRepository)
    {
    }

	public function __invoke(Request $request, Response $response)
	{
		$scan = $this->scansRepository->find($request->getQueryParams()['id']);

		if ($request->getMethod() == 'POST') {
			$scanData = $request->getParsedBody();

            $scan->setCheckIn(strlen($scanData['checkIn']) > 0 ? new \Datetime($scanData['checkIn']) : null);
            $scan->setCheckOut(strlen($scanData['checkOut']) > 0 ? new \Datetime($scanData['checkOut']) : null);
			$this->scansRepository->persist($scan);

            return $response->withHeader('Location', '/admin/scans')->withStatus(302);
    	}

		return $this->twig->render($response, 'admin/update_scan.twig', ['scan' => $scan]);
	}
}