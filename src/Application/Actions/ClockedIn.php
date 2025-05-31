<?php

declare(strict_types=1);

namespace Application\Actions;

use Domain\Repositories\ScansRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ClockedIn
{
	private $scansRepository;

	public function __construct(ScansRepositoryInterface $scansRepository)
	{
		$this->scansRepository = $scansRepository;
	}

	public function __invoke(Request $request, Response $response)
	{
		$scans = $this->scansRepository->findBy(['checkOut' => null]);
		$now = new \Datetime;

		$body = '<p>';
		foreach($scans as $scan) {
			$body .= sprintf("%s <i class=\"fa-solid fa-arrow-right-long\"></i> %s<br/>",
				$scan->getUser()->getFullName(),
				$now->diff($scan->getCheckIn())->format('%H:%I:%S')
			);
		}
		$body .= '</p>';
		$response->getBody()->write($body);

		return $response;
	}
}