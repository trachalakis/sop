<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Datetime;
use DateTimeImmutable;
use Application\Services\OrdersReportService;
use Domain\Repositories\ScansRepository;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\MenuSectionsRepository;
use Doctrine\Common\Collections\Criteria;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Report
{
    public function __construct(
        private MenuSectionsRepository $menuSectionsRepository,
        private OrdersRepository $ordersRepository,
        private OrdersReportService $ordersReportService,
        private ScansRepository $scansRepository,
        private Twig $twig
    ) {
    }

	public function __invoke(Request $request, Response $response)
	{
		$ordersRepository = $this->ordersRepository;
		$queryParams = $request->getQueryParams();
		$filter = $queryParams['filter'] ?? null;

        if (!empty($filter['start']) && !empty($filter['end'])) {
	        $start = new Datetime($filter['start']);
	        $end = new Datetime($filter['end']);
	    } else if (!empty($filter['start']) && empty($filter['end'])){
            $start = new Datetime($filter['start'] . ' 05:00');
            $end = (clone $start)->add(new \DateInterval('PT21H'));
        } else if ((new Datetime())->format('G') <= 4) {
            $start = new Datetime('yesterday 05:00');
            $end = (clone $start)->add(new \DateInterval('PT21H'));
        } else {
            $start = new Datetime('today 05:00');
            $end = (clone $start)->add(new \DateInterval('PT21H'));
        }

        $start = DateTimeImmutable::createFromMutable($start);
        $end = DateTimeImmutable::createFromMutable($end);

	    $criteria = new Criteria;
        $criteria->andWhere(Criteria::expr()->gte('createdAt', $start));
	    $criteria->andWhere(Criteria::expr()->lte('createdAt', $end));
        $orders = $ordersRepository->matching($criteria->orderBy(['createdAt' => 'asc']));

        if (isset($filter['service']) && $filter['service'] == 'lunch') {
            $orders = $orders->filter(function ($order) {
                return $order->getCreatedAt()->format('G') < 18;
            });
        }

        if (isset($filter['service']) && $filter['service'] == 'dinner') {
            $orders = $orders->filter(function ($order) {
                return $order->getCreatedAt()->format('G') >= 18;
            });
        }

        $report = $this->ordersReportService->buildReport($orders, $end);

        $sales          = $report['sales'];
        $salesTakeAway  = $report['salesTakeAway'];
        $coversAdults   = $report['coversAdults'];
        $coversMinors   = $report['coversMinors'];
        $totalWeight    = $report['totalWeight'];
        $foodCost       = $report['foodCost'];
        $menuSections   = $report['menuSections'];
        $servedPlates   = $report['servedPlates'];
        $servedDrinks   = $report['servedDrinks'];
        $servedMenuItems = $servedPlates + $servedDrinks;

        $coversPerHourData = [];
        $ordersPerHourData = [];
        $salesPerHourData  = [];

        foreach ($orders as $order) {
            if ($order->getTable() === null) {
                continue;
            }
            if (isset($coversPerHourData[$order->getCreatedAt()->format('G')])) {
                $ordersPerHourData[$order->getCreatedAt()->format('G')] += 1;
                $coversPerHourData[$order->getCreatedAt()->format('G')] += $order->getAdults() + $order->getMinors();
                $salesPerHourData[$order->getCreatedAt()->format('G')]  += $order->getPrice();
            } else {
                $ordersPerHourData[$order->getCreatedAt()->format('G')] = 1;
                $coversPerHourData[$order->getCreatedAt()->format('G')] = $order->getAdults() + $order->getMinors();
                $salesPerHourData[$order->getCreatedAt()->format('G')]  = $order->getPrice();
            }
        }

        $chartLabels = array_keys($coversPerHourData);
        sort($chartLabels);

        ksort($coversPerHourData);
        ksort($salesPerHourData);
        ksort($ordersPerHourData);

        $start = DateTime::createFromImmutable($start);
        $end = DateTime::createFromImmutable($end);

        $criteria = new Criteria;
        $criteria->andWhere(Criteria::expr()->gte('checkIn', $start));
	    $criteria->andWhere(Criteria::expr()->lte('checkIn', $end));
        $scans = $this->scansRepository->matching($criteria);

        $manHours = 0;
        $manMinutes = 0;
        $manSeconds = 0;
        $salaries = 0;
        $bohSalaries = 0;
        $fohSalaries = 0;
        foreach($scans as $scan) {
        	$interval = $scan->getInterval();

            if ($interval != null) {
	            $manHours += $interval->h;
	            $manMinutes += $interval->i;
	            $manSeconds += $interval->s;
	            if ($manSeconds >= 60) {
	                $manMinutes++;
	                $manSeconds -= 60;
	            }

	            if ($manMinutes >= 60) {
	                $manHours++;
	                $manMinutes -= 60;
	            }

	            $salaries += $scan->getSalary();

                $userRoleNames = array_map(fn($r) => $r->getName(), $scan->getUser()->getRoles());
                if (in_array('foh', $userRoleNames)) {
                    $fohSalaries += $scan->getSalary();
                }

                if (in_array('boh', $userRoleNames)) {
                    $bohSalaries += $scan->getSalary();
                }
	        }
        }

        /*** Statistics ***/
        $standardDeviation = 0;
        if (count($orders) > 10) {
            $salesMean = $sales / count($orders);
            $squaresSum = 0;
            foreach($orders as $order) {
                $squaresSum += ($order->getPrice() - $salesMean) ** 2;
            }
            $standardDeviation = round(sqrt($squaresSum / (count($orders) - 1)), 2);
        }

        $oneYearAgo = (clone $start)->sub(new \DateInterval('P1Y'));


        return $this->twig->render(
            $response,
            'admin/report.twig',
            [
                'filter' => $filter,
            	'start'=> $start,
                'end' => $end,
                'previousDay' => (clone $start)->sub(new \DateInterval('P1D')),
                'nextDay' => (clone $start)->add(new \DateInterval('P1D')),
            	'orders' => $orders,
            	'sales' => $sales,
            	'salesTakeAway' => $salesTakeAway,
            	'coversAdults' => $coversAdults,
            	'coversMinors' => $coversMinors,
                'totalWeight' => $totalWeight,
                'menuSections' => $menuSections,

                'manHours' => $manHours,
                'manMinutes' => $manMinutes,
                'manSeconds' => $manSeconds,

                'salaries' => $salaries,
                'fohSalaries' => $fohSalaries,
                'bohSalaries' => $bohSalaries,

                'ordersPerHourData' => $ordersPerHourData,
                'coversPerHourData' => $coversPerHourData,
                'salesPerHourData' => $salesPerHourData,
                'chartLabels' => $chartLabels,

                'oneYearAgo' => $oneYearAgo,

                'standardDeviation' => $standardDeviation,

                'foodCost' => $foodCost,

                'servedMenuItems' => $servedMenuItems,
                'servedPlates' => $servedPlates,
                'servedDrinks' => $servedDrinks,
            ]
        );
	}
}
