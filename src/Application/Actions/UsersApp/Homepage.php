<?php

declare(strict_types=1);

namespace Application\Actions\UsersApp;

use Datetime;
use DateInterval;
use DatePeriod;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Domain\Repositories\ScansRepository;
use Domain\Repositories\OrdersRepository;
use Slim\Views\Twig;

final class Homepage
{
    public function __construct(
        private OrdersRepository $ordersRepository,
        private ScansRepository $scansRepository,
        private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $lastCheckIn = $this->scansRepository->findLastUserCheckIn($_SESSION['user']);

        //Scans card
        $hours = 0;
        $minutes = 0;

        $startDate = (new Datetime)->modify('today midnight');
        if ((new Datetime)->format('G') <= 4) {
            $startDate = new Datetime('yesterday');
        }
        $interval = new DateInterval('P1D');
        $endDate = (clone $startDate)->add($interval);
        $period = new DatePeriod($startDate, $interval, $endDate);

        $todaysScans = $this->scansRepository->findUserScans($_SESSION['user'], $period);

        foreach($todaysScans as $scan) {
            $interval = $scan->getInterval();
            if ($interval == null) {
                $interval = (new Datetime)->diff($scan->getCheckIn());
            }
            $hours += $interval->h;
            $minutes += $interval->i;

            if ($minutes >= 60) {
                $hours++;
                $minutes = $minutes - 60;
            }
        }

        //Orders card
        $adults = 0;
        $minors = 0;
        $openOrders = $this->ordersRepository->findBy(['status' => 'OPEN'], ['createdAt' => 'desc']);

        foreach($openOrders as $order) {
            $adults += $order->getAdults();
            $minors += $order->getMinors();
        }

        //Self delivery card
        $selfDeliveries = $this->ordersRepository->findBy(['employee' => $_SESSION['user']]);

        return $this->twig->render($response, 'users_app/homepage.twig', [
            'user' => $_SESSION['user'],
            'userIsWorking' => $lastCheckIn != null,
            'hours' => $hours,
            'minutes' => $minutes,

            'openOrders' => $openOrders,
            'adults' => $adults,
            'minors' => $minors,

            'selfDeliveries' => $selfDeliveries
        ]);
    }
}