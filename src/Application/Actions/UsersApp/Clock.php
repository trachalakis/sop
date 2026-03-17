<?php

declare(strict_types=1);

namespace Application\Actions\UsersApp;

use Datetime;
use Domain\Entities\Scan;
use Domain\Repositories\UsersRepository;
use Domain\Repositories\ScansRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Clock
{
	private ScansRepository $scansRepository;

	private Twig $twig;

	private UsersRepository $usersRepository;

	public function __construct(
		ScansRepository $scansRepository,
		Twig $twig,
		UsersRepository $usersRepository
	) {
		$this->scansRepository = $scansRepository;
		$this->twig = $twig;
		$this->usersRepository = $usersRepository;
	}

	public function __invoke(Request $request, Response $response)
	{
		try {
			$now = new Datetime;
			$user = $this->usersRepository->find($_SESSION['user']->getId());
			$scan = $this->scansRepository->findLastUserCheckIn($user);
			if ($scan == null) {
				$scan = new Scan(
					$user->getHourlyRate(),
					$now,
					null,
					$user
				);

				$this->scansRepository->add($scan);

				$response->getBody()->write('Εναρξη εργασίας ' . $now->format('H:i:s'));
			} else {
				$checkOut = new \Datetime();

				if ($checkOut->diff($scan->getCheckIn())->days > 0) {
					//throw new \Exception('Scan more than one day after last scan' . $checkOut->diff($scan->getCheckIn())->days);
				}

				$scan->setCheckOut($checkOut);
				$this->scansRepository->persist($scan);

				$response->getBody()->write(sprintf(
					"Τέλος εργασίας %s. Ωρες %s, λεπτά %s",
					$now->format('H:i:s'), $now->diff($scan->getCheckIn())->h , $now->diff($scan->getCheckIn())->i
				));
			}
		} catch(\Exception $e) {
			$response->getBody()->write($e->getMessage());
		}

		return $response;
	}
}