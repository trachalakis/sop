<?php

declare(strict_types=1);

namespace Application\Services;

use DateTimeInterface;
use Domain\Repositories\RecipesRepository;

final class OrdersReportService
{
    public function __construct(
        private RecipesRepository $recipesRepository
    ) {
    }

    /**
     * Compute all order-derived metrics for a set of orders.
     *
     * @param iterable       $orders  Order entities for the period.
     * @param DateTimeInterface $end  Reference date for ingredient cost lookups.
     * @return array{
     *   sales: float,
     *   salesTakeAway: float,
     *   coversAdults: int,
     *   coversMinors: int,
     *   totalWeight: float,
     *   foodCost: float,
     *   menuSections: array,
     *   servedPlates: int,
     *   servedDrinks: int,
     * }
     */
    public function buildReport(iterable $orders, DateTimeInterface $end): array
    {
        // Convert to array so we can iterate twice (served counts use all orders,
        // not just table orders).
        $ordersArray = is_array($orders) ? $orders : iterator_to_array($orders);

        $recipesByMenuItemId = [];
        foreach ($this->recipesRepository->findMenuItemRecipes() as $recipe) {
            $recipesByMenuItemId[$recipe->getMenuItem()->getId()] = $recipe;
        }

        $sales         = 0.0;
        $salesTakeAway = 0.0;
        $coversAdults  = 0;
        $coversMinors  = 0;
        $totalWeight   = 0.0;
        $foodCost      = 0.0;
        $menuSections  = [];

        foreach ($ordersArray as $order) {
            // Orders without a table (take-out from orders-app) are excluded from
            // table-based metrics. salesTakeAway is always 0 here — matching the
            // existing Report.php behaviour where the null-table guard prevents it
            // from ever being incremented.
            if ($order->getTable() === null) {
                continue;
            }

            $sales += $order->getPrice();

            if (!$order->isDrinksOnly()) {
                $coversAdults += $order->getAdults();
                $coversMinors += $order->getMinors();
            }

            foreach ($order->getOrderEntries() as $orderEntry) {
                $menuItem = $orderEntry->getMenuItem();
                if ($menuItem === null) {
                    continue;
                }

                $menuSection      = $menuItem->getMenuSection();
                $menuSectionIndex = $menuSection->getId();

                if (!isset($menuSections[$menuSectionIndex])) {
                    $menuSections[$menuSectionIndex] = [
                        'menuSection' => $menuSection,
                        'count'       => 0,
                        'sales'       => 0.0,
                        'menuItems'   => [],
                    ];
                }

                $menuItemIndex = $menuItem->getId();
                if (!isset($menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex])) {
                    $menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex] = [
                        'menuItem' => $menuItem,
                        'count'    => 0,
                        'weight'   => 0.0,
                        'sales'    => 0.0,
                    ];
                }

                $menuSections[$menuSectionIndex]['count']                                       += $orderEntry->getQuantity();
                $menuSections[$menuSectionIndex]['sales']                                       += $orderEntry->getPrice();
                $menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex]['count']          += $orderEntry->getQuantity();
                $menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex]['sales']          += $orderEntry->getPrice();
                $menuSections[$menuSectionIndex]['menuItems'][$menuItemIndex]['weight']         += ($orderEntry->getQuantity() * $orderEntry->getWeight());

                $totalWeight += ($orderEntry->getWeight() * $orderEntry->getQuantity());

                $recipe = $recipesByMenuItemId[$menuItem->getId()] ?? null;
                /*if ($recipe !== null && $recipe->getYield() > 0) {
                    $foodCost += $orderEntry->getQuantity() * ($recipe->getFoodCost($end) / $recipe->getYield());
                }*/
            }
        }

        // Served counts include ALL orders (not just table orders)
        $servedPlates = 0;
        $servedDrinks = 0;
        foreach ($ordersArray as $order) {
            foreach ($order->getOrderEntries() as $orderEntry) {
                if ($orderEntry->getPrice() == 0) {
                    continue;
                }
                if ($orderEntry->getMenuItem() === null) {
                    continue;
                }
                if ($orderEntry->getMenuItem()->getIsDrink()) {
                    $servedDrinks += $orderEntry->getQuantity();
                } else {
                    if ($orderEntry->getMenuItem()->getId() === 1) {
                        $servedPlates++;
                    } else {
                        $servedPlates += $orderEntry->getQuantity();
                    }
                }
            }
        }

        uasort($menuSections, fn($a, $b) => $a['menuSection']->getPosition() <=> $b['menuSection']->getPosition());

        return [
            'sales'         => $sales,
            'salesTakeAway' => $salesTakeAway,
            'coversAdults'  => $coversAdults,
            'coversMinors'  => $coversMinors,
            'totalWeight'   => $totalWeight,
            'foodCost'      => $foodCost,
            'menuSections'  => $menuSections,
            'servedPlates'  => $servedPlates,
            'servedDrinks'  => $servedDrinks,
        ];
    }
}
