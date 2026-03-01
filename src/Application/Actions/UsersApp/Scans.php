<?php

declare(strict_types=1);

namespace Application\Actions\UsersApp;

use Domain\Repositories\ScansRepository;
use Domain\Entities\User;
use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Scans
{
	private $twig;

    private $scansRepository;

    private $usersRepository;

    public function __construct(Twig $twig, ScansRepository $scansRepository, UsersRepository $usersRepository)
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
        $startOfMonth = (clone $date)->modify('first day of this month');
        $endOfMonth = (clone $date)->modify('first day of next month');
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($startOfMonth, $interval, $endOfMonth);

        $scans = $this->scansRepository->findUserScans($user, $period);

        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        foreach ($scans as $scan) {
            $interval = $scan->getInterval();

            if ($interval != null) {
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