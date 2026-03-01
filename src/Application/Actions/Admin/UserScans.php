<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\ScansRepository;
use Domain\Entities\User;
use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UserScans
{
	private Twig $twig;

    private ScansRepository $scansRepository;

    private UsersRepository $usersRepository;

    public function __construct(
        ScansRepository $scansRepository,
        Twig $twig,
        UsersRepository $usersRepository
    ) {
        $this->twig = $twig;
        $this->usersRepository = $usersRepository;
        $this->scansRepository = $scansRepository;
    }

	public function __invoke(Request $request, Response $response)
	{
		$user = $this->usersRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

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
            'admin/user_scans.twig',
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