<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Datetime;
use Domain\Repositories\ScansRepositoryInterface;
use Domain\Repositories\OrdersRepositoryInterface;
use Domain\Repositories\MenuSectionsRepositoryInterface;
use Doctrine\Common\Collections\Criteria;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Report
{
    private MenuSectionsRepositoryInterface $menuSectionsRepository;

    private OrdersRepositoryInterface $ordersRepository;

    private ScansRepositoryInterface $scansRepository;

    private Twig $twig;

    public function __construct(
    	MenuSectionsRepositoryInterface $menuSectionsRepository,
    	OrdersRepositoryInterface $ordersRepository,
    	ScansRepositoryInterface $scansRepository,
    	Twig $twig
    ) {
    	$this->menuSectionsRepository = $menuSectionsRepository;
        $this->ordersRepository = $ordersRepository;
        $this->scansRepository = $scansRepository;
        $this->twig = $twig;
    }

	public function __invoke(Request $request, Response $response)
	{
		$ordersRepository = $this->ordersRepository;
		$queryParams = $request->getQueryParams();
		$filter = $queryParams['filter'] ?? null;
        $date = $queryParams['date'] ?? null;
		if ((new Datetime())->format('G') <= 4) {
			$start = new Datetime('yesterday 05:00');
		} else {
			$start = new Datetime('today 05:00');
		}
		$end = (clone $start)->add(new \DateInterval('PT18H'));

		if (!empty($date)) {
			$start = new Datetime($date . ' 5:00:00 AM');
			$end = (clone $start)->add(new \DateInterval('PT21H'));
		} else if (!empty($filter['start']) && !empty($filter['end'])){
	        $start = new Datetime($filter['start']);
	        $end = new Datetime($filter['end']);
	    }

	    $criteria = new Criteria;
        $criteria->andWhere(Criteria::expr()->gte('createdAt', $start));
	    $criteria->andWhere(Criteria::expr()->lte('createdAt', $end));
        $orders = $ordersRepository->matching($criteria->orderBy(['createdAt' => 'asc']));

        $sales = 0;
        $salesTakeAway = 0;
        $coversAdults = 0;
        $coversMinors = 0;
        $totalWeight = 0;
        $menuSections = [];

        $coversPerHourData = [];
        $ordersPerHourData = [];
        $salesPerHourData = [];

        if ($filter['service'] == 'lunch') {
            $orders = $orders->filter(function ($order) {
                return $order->getCreatedAt()->format('G') < 18;
            });
        }

        if ($filter['service'] == 'dinner') {
            $orders = $orders->filter(function ($order) {
                return $order->getCreatedAt()->format('G') >= 18;
            });
        }

        /*$employeeOrders = [];
        foreach($orders as $order) {
            if ($order->getTable() == null) {
            	$employeeOrders[] = $order;
            
                $orders->removeElement($order);
            }
        }*/

        foreach($orders as $order) {
            $sales += $order->getPrice();

            if ($order->getTable() == null) {
                $salesTakeAway += $order->getPrice();
            }

            if ($order->getTable() != null && !$order->isDrinksOnly()) {
                $coversAdults += $order->getAdults();
                $coversMinors += $order->getMinors();
            }

            foreach($order->getOrderEntries() as $orderEntry) {
                $menuItem = $orderEntry->getMenuItem();
                $menuSection = $menuItem->getMenuSection();

                $menuSectionIndex = $menuSection->getId();
                if (!isset($menuSections[$menuSectionIndex])) {
                    $menuSections[$menuSectionIndex]['menuSection'] = $menuSection;
                    $menuSections[$menuSectionIndex]['count'] = 0;
                    $menuSections[$menuSectionIndex]['sales'] = 0;
                }

                $menuItemIndex = $menuItem->getId();
                if(!isset($menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex])) {
                    $menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex]['menuItem'] = $menuItem;
                    $menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex]['count'] = 0;
                    $menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex]['weight'] = 0;
                    $menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex]['sales'] = 0;
                }

                $menuSections[$menuSectionIndex]['count'] += $orderEntry->getQuantity();
                $menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex]['weight'] += ($orderEntry->getQuantity() * $orderEntry->getWeight());
                $menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex]['count'] += $orderEntry->getQuantity();
                $menuSections[$menuSectionIndex]['sales'] += $orderEntry->getPrice();
                $menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex]['sales'] += $orderEntry->getPrice();

                $totalWeight += ($orderEntry->getWeight() * $orderEntry->getQuantity());
            }

            if(isset($coversPerHourData[$order->getCreatedAt()->format('G')])) {
            	$ordersPerHourData[$order->getCreatedAt()->format('G')] += 1;
            	$coversPerHourData[$order->getCreatedAt()->format('G')] += $order->getAdults() + $order->getMinors();
            	$salesPerHourData[$order->getCreatedAt()->format('G')] += $order->getPrice();
            } else {
            	$ordersPerHourData[$order->getCreatedAt()->format('G')] = 1;
            	$coversPerHourData[$order->getCreatedAt()->format('G')] = $order->getAdults() + $order->getMinors();
            	$salesPerHourData[$order->getCreatedAt()->format('G')] = $order->getPrice();
            }
        }

        $chartLabels = array_keys($coversPerHourData);
        sort($chartLabels);

        ksort($coversPerHourData);
        ksort($salesPerHourData);
        ksort($ordersPerHourData);

        uasort($menuSections, function($a, $b) {
        	if ($a['menuSection']->getPosition() < $b['menuSection']->getPosition()) {
        		return -1;
        	} else if ($a['menuSection']->getPosition() > $b['menuSection']->getPosition()) {
        		return 1;
        	} else {
        		return 0;
        	}
        });

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

                if (in_array('foh', $scan->getUser()->getRoles())) {
                    $fohSalaries += $scan->getSalary();
                }

                if (in_array('boh', $scan->getUser()->getRoles())) {
                    $bohSalaries += $scan->getSalary();
                }
	        }
        }

        $oneYearAgo = (clone $start)->sub(new \DateInterval('P1Y'));

        /*** Statistics ***/
        if (count($orders) > 0) {
            $salesMean = $sales / count($orders);
            $squaresSum = 0;
            foreach($orders as $order) {
                $squaresSum += ($order->getPrice() - $salesMean) ** 2;
            }
            $standardDeviation = round(sqrt($squaresSum / (count($orders) - 1)), 2);
        }

        $servedMenuItems = 0;
        $servedDrinks = 0;
        foreach($orders as $order) {
            /*if ($order->getTable() == null) {
                $orders->removeElement($order);
            	continue;
            }*/

            foreach($order->getOrderEntries() as $orderEntry) {
                if ($orderEntry->getPrice() == 0) {
                    continue;
                }

                if ($orderEntry->getMenuItem()->getId() == 1) {
                    $servedMenuItems++;
                } else {
                    if ($orderEntry->getMenuItem()->getIsDrink()) {
                        $servedDrinks += $orderEntry->getQuantity();
                    }
                    
                    $servedMenuItems += $orderEntry->getQuantity();
                }
            }
        }

        //$labourCost = 0;
        //if ($servedMenuItems != 0) {
        //    $labourCost = $salaries / $servedMenuItems;
        //}


        return $this->twig->render(
            $response,
            'admin/report.twig',
            [
                'filter' => $filter,
            	'start'=> $start,
                'end' => $end,
                'prev' => (clone $start)->sub(new \DateInterval('P1D')),
                'next' => (clone $start)->add(new \DateInterval('P1D')),
            	'orders' => $orders,
            	'employeeOrders' => $employeeOrders,
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

                'servedMenuItems' => $servedMenuItems,
                'servedDrinks' => $servedDrinks,
                //'labourCost' => $labourCost
            ]
        );
	}

	private function ordersReport($orders)
	{
		$report = [
			'sales' => 0,
			'salesTakeAway' => 0,
			'adultCovers' => 0,
			'minorCovers' => 0,
			'fishWeight' => 0,
			'menuSales' => []
		];

		foreach($orders as $order) {
        	$report['sales'] += $order->getPrice();

            //for now orders with only drinks only add to sales
            if ($order->isDrinksOnly()) {
            	continue;
            }

            if ($order->getTable()->getName() != 'Take away') {
                $report['salesTakeAway'] += $order->getPrice();
                $report['adultCovers'] += $order->getAdults();
                $report['minorCovers'] += $order->getMinors();
            }

            foreach($order->getOrderEntries() as $orderEntry) {
                $menuItem = $orderEntry->getMenuItem();
                $menuSection = $menuItem->getMenuSection();

                $menuItemIndex = $menuItem->getId();
                $menuSales[$menuItemIndex]['menuItem'] == $menuSales[$menuItemIndex]['menuItem'] ?? $menuItem;
                $menuSales[$menuItemIndex]['count'] == $menuSales[$menuItemIndex]['count'] ?? 0;
                $menuSales[$menuItemIndex]['sales'] == $menuSales[$menuItemIndex]['sales'] ?? 0;
                $menuSales[$menuItemIndex]['weight'] == $menuSales[$menuItemIndex]['weight'] ?? 0;

                $menuSales[$menuItemIndex]['count'] += $orderEntry->getQuantity();
                $menuSales[$menuItemIndex]['sales'] += $orderEntry->getPrice();
                $menuSales[$menuItemIndex]['weight'] += $orderEntry->getWeight();

                $totalWeight += ($orderEntry->getWeight() * $orderEntry->getQuantity());
            }
        }

        return $report;
	}
}