<?php

declare(strict_types=1);

namespace Application\Actions\UsersApp;

use Domain\Repositories\ScansRepositoryInterface;
use Domain\Entities\User;
use Domain\Repositories\UsersRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Scans
{
	private $twig;

    private $scansRepository;

    private $usersRepository;

    public function __construct(Twig $twig, ScansRepositoryInterface $scansRepository, UsersRepositoryInterface $usersRepository)
    {
        $this->twig = $twig;
        $this->usersRepository = $usersRepository;
        $this->scansRepository = $scansRepository;
    }

	public function __invoke(Request $request, Response $response)
	{
		$user = $this->usersRepository->findOneBy(['id' => $_SESSION['user']->getId()]);

		$queryParams = $request->getQueryParams();
        if (empty($queryParams['date'])) {
            $date = new \Datetime;
        } else {
            $date = new \Datetime($queryParams['date']);
        }
        $startOfMonth = $date->modify('first day of this month');

        $scans = $this->scansRepository->findUserCheckIns($user, $startOfMonth->format('Y-m'));

        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        foreach ($scans as $scan) {
            $interval = $scan->getInterval();

            $hours += $interval->h;
            $minutes += $interval->i;
            $seconds += $interval->s;

            if ($seconds >= 60) {
                $minutes++;
                $seconds = $seconds - 60;
            }

            if ($minutes >= 60) {
                $hours++;
                $minutes = $minutes - 60;
            }
        }

		return $this->twig->render(
            $response,
            'users_app/scans.twig',
            [
                'user' => $user,
                'scans' => $scans,
                'startOfMonth' => $startOfMonth,
                'prev' => (clone $startOfMonth)->modify('first day of previous month'),
                'next' => (clone $startOfMonth)->modify('first day of next month'),
                'hours' => $hours,
                'minutes' => $minutes,
                'seconds' => $seconds,
            ]
        );
	}
}