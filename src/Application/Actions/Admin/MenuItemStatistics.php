<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Datetime;
use DatePeriod;
use DateInterval;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\OrderEntriesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class MenuItemStatistics
{
	private Twig $twig;

	private MenuItemsRepository $menuItemsRepository;

	private OrderEntriesRepository $orderEntriesRepository;

	public function __construct(
		Twig $twig,
		MenuItemsRepository $menuItemsRepository,
		OrderEntriesRepository $orderEntriesRepository,
	) {
		$this->twig = $twig;
		$this->menuItemsRepository = $menuItemsRepository;
		$this->orderEntriesRepository = $orderEntriesRepository;
	}

	public function __invoke(Request $request, Response $response)
	{
		$menuItem = $this->menuItemsRepository->find($request->getQueryParams()['id']);
		
		$filter = $request->getQueryParams()['filter'] ?? [];
		if (!empty($filter)) {
			$startDate = new Datetime($filter['start']);
			$endDate = new Datetime($filter['end']);
			$period = null;
		} else {
			$period = $request->getQueryParams()['period'] ?? 'week';
			if ($period == 'year') {
				$startDate = new Datetime('first day of january this year midnight');
				$endDate = new Datetime('last day of december this year midnight');
			} else if ($period == 'month') {
				$startDate = new Datetime('first day of this month midnight');
				$endDate = new Datetime('last day of this month midnight');
			} else {
				$startDate = new Datetime('monday this week');
				$endDate = (clone $startDate)->add(new \DateInterval('P7D'));
			}
		}
		$datePeriod = new DatePeriod($startDate, new DateInterval('P1D'), $endDate);

		$orderEntries = $this->orderEntriesRepository->findByMenuItemAndPeriod($menuItem, $datePeriod);
		
        $days = [];
        $totalSales = 0;
        $count = 0;
        foreach($orderEntries as $orderEntry) {
        	$date = $orderEntry->getCreatedAt();
        	if ($date == null) {
        		$date = $orderEntry->getOrderEntryGroup()->getCreatedAt();
        	}
        	$dateYmd = $date->format('D d/m/Y');

        	
			$totalSales += $orderEntry->getPrice();
			$count += $orderEntry->getQuantity();

			if (isset($days[$dateYmd])) {
				$days[$dateYmd] += $orderEntry->getQuantity();
			} else {
				$days[$dateYmd] = $orderEntry->getQuantity();
			}
        }

        return $this->twig->render(
            $response,
            'admin/menu_item_statistics.twig',
            [
            	'menuItem' => $menuItem,
				'period' => $period,
				'totalSales' => $totalSales,
            	'count' => $count,
            	//'maxCount' => $maxCount,
            	//'minCount' => $minCount,
            	
            	'days' => $days,
            	//'consumptions' => $suppliesMatrix
            	//'timePeriod' => $timePeriod,
            	'startDate' => $startDate,
            	'endDate' => $endDate
            ]
        );
	}
}