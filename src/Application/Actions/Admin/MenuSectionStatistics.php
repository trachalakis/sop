<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Datetime;
use DatePeriod;
use DateInterval;
use Domain\Repositories\OrderEntriesRepositoryInterface;
use Domain\Repositories\MenuSectionsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class MenuSectionStatistics
{
	private Twig $twig;

	private OrderEntriesRepositoryInterface $orderEntriesRepository;

	private MenuSectionsRepositoryInterface $menuSectionsRepository;

	public function __construct(
		Twig $twig,
		OrderEntriesRepositoryInterface $orderEntriesRepository,
		MenuSectionsRepositoryInterface $menuSectionsRepository
	) {
		$this->twig = $twig;
		$this->orderEntriesRepository = $orderEntriesRepository;
		$this->menuSectionsRepository = $menuSectionsRepository;
	}

	public function __invoke(Request $request, Response $response)
	{
		$menuSection = $this->menuSectionsRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

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
		$datePeriod = new DatePeriod($startDate, new DateInterval('P1D'), $endDate);

		
		$menuItems = [];
		$sales = [];
		$totalSales = 0;
        $count = 0;

		//TODO implement code for orderEntries before 2024, that do not belong to an orderEntryGroup
		foreach($menuSection->getMenuItems() as $menuItem) {
			$menuItems[$menuItem->getId()] = ['menuItem' => $menuItem, 'count' => 0, 'totalSales' => 0, 'weight' => 0];

			$orderEntries = $this->orderEntriesRepository->findByMenuItemAndPeriod($menuItem, $datePeriod);
			
	        foreach($orderEntries as $orderEntry) {
				$totalSales += $orderEntry->getPrice();
				$count += $orderEntry->getQuantity();
				$menuItems[$menuItem->getId()]['count'] += $orderEntry->getQuantity();
				$menuItems[$menuItem->getId()]['totalSales'] += $orderEntry->getPrice();
				if ($orderEntry->getWeight() != null) {
					$menuItems[$menuItem->getId()]['weight'] += $orderEntry->getWeight();
				}
	        
				//Sales breakdown
				$date = $orderEntry->getOrderEntryGroup()->getCreatedAt()->format('D d/m/Y'); 
				if (isset($sales[$date])) {
					$sales[$date] += $orderEntry->getPrice();
				} else {
					$sales[$date] = $orderEntry->getPrice();
				}
			}
	    }

	    uasort($menuItems, function ($a, $b) {
	    	return $a['count'] < $b['count'];
	    });

        return $this->twig->render(
            $response,
            'admin/menu_section_statistics.twig',
            [
            	'period' => $period,
				'totalSales' => $totalSales,
            	'count' => $count,
            	'menuSection' => $menuSection,
            	'menuItems' => $menuItems,

				'sales' => $sales
            ]
        );
	}
}