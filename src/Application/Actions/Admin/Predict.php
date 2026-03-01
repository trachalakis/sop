<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Datetime;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\MenuSectionsRepository;
use Doctrine\Common\Collections\Criteria;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Predict
{
    private MenuSectionsRepository $menuSectionsRepository;

    private OrdersRepository $ordersRepository;

    private Twig $twig;

    public function __construct(
    	MenuSectionsRepository $menuSectionsRepository,
    	OrdersRepository $ordersRepository,
    	Twig $twig
    ) {
    	$this->menuSectionsRepository = $menuSectionsRepository;
        $this->ordersRepository = $ordersRepository;
        $this->twig = $twig;
    }

	public function __invoke(Request $request, Response $response)
	{
		$queryParams = $request->getQueryParams();

        $date = new \Datetime($queryParams['date']);
        $dayBefore = (clone $date)->sub(new \DateInterval('P1D'));
        $oneWeekBefore = (clone $date)->sub(new \DateInterval('P1W'));
        $twoWeeksBefore = (clone $date)->sub(new \DateInterval('P2W'));

        //$dateReport = $this->ordersReport($this->ordersRepository->findByDate($today));
        //$dayBeforeReport = $this->ordersReport($this->ordersRepository->findByDate($dayBefore));
        $oneWeekBeforeReport = $this->ordersReport($this->ordersRepository->findByDate($oneWeekBefore));
        $twoWeeksBeforeReport = $this->ordersReport($this->ordersRepository->findByDate($twoWeeksBefore));

        $prediction['sales'] = ($oneWeekBeforeReport['sales'] + $twoWeeksBeforeReport['sales']) / 2;
        if ($oneWeekBeforeReport['sales'] > $twoWeeksBeforeReport['sales']) {
			dd('xoxo');
        	$prediction['sales'] += $oneWeekBeforeReport['sales'] - $twoWeeksBeforeReport['sales'];
        }
        $prediction['salesTakeAway'] = ($oneWeekBeforeReport['salesTakeAway'] + $twoWeeksBeforeReport['salesTakeAway']) / 2;
        $prediction['adultCovers'] = ($oneWeekBeforeReport['adultCovers'] + $twoWeeksBeforeReport['adultCovers']) / 2;
        $prediction['minorCovers'] = ($oneWeekBeforeReport['minorCovers'] + $twoWeeksBeforeReport['minorCovers']) / 2;
        $prediction['fishWeight'] = ($oneWeekBeforeReport['fishWeight'] + $twoWeeksBeforeReport['fishWeight']) / 2;

        return $this->twig->render(
            $response,
            'admin/predict.twig',
            [
            	'date'=> $date,
                'prev' => (clone $date)->sub(new \DateInterval('P1D')),
                'next' => (clone $date)->add(new \DateInterval('P1D')),
            	'prediction' => $prediction
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