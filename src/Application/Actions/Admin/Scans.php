<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\ScansRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Scans
{
	private Twig $twig;

	private ScansRepositoryInterface $scansRepository;

	public function __construct(Twig $twig, ScansRepositoryInterface $scansRepository)
	{
		$this->twig = $twig;

		$this->scansRepository = $scansRepository;
	}

	public function __invoke(Request $request, Response $response)
	{
		$queryParams = $request->getQueryParams();
		if (empty($queryParams['date'])) {
			$date = new \Datetime();
			if ($date->format('G') <= 6) {
				$date = new \Datetime('yesterday');
			}
		} else {
			$date = new \Datetime($queryParams['date']);
		}

		$scans = $this->scansRepository->findCheckInsByDate($date);

		$hours = 0;
        $minutes = 0;
        $seconds = 0;
        $salariesTotal = 0;
        foreach ($scans as $scan) {
            $interval = $scan->getInterval();

            if ($interval === null) {
            	continue;
            }

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

            $salariesTotal += $scan->getSalary();
        }

		return $this->twig->render(
			$response,
			'admin/scans.twig',
			[
				'scans' => $scans,
				'date' => $date,
				'prev' => (clone $date)->sub(new \DateInterval('P1D')),
				'next' => (clone $date)->add(new \DateInterval('P1D')),
				'hours' => $hours,
                'minutes' => $minutes,
                'seconds' => $seconds,
                'salariesTotal' => $salariesTotal
			]
		);
	}
}