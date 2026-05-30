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
     *   discountsTotal: float,
     *   cancellationsCost: float,
     *   menuSections: array,
     *   servedPlates: int,
     *   servedDrinks: int,
     * }
     */
    public function buildReport(iterable $orders, DateTimeInterface $end): array
    {
        $ordersArray = is_array($orders) ? $orders : iterator_to_array($orders);

        $recipesByMenuItemId = [];
        foreach ($this->recipesRepository->findMenuItemRecipes() as $recipe) {
            $recipesByMenuItemId[$recipe->getMenuItem()->getId()] = $recipe;
        }

        $sales             = 0.0;
        $salesTakeAway     = 0.0;
        $coversAdults      = 0;
        $coversMinors      = 0;
        $totalWeight       = 0.0;
        $foodCost          = 0.0;
        $discountsTotal    = 0.0;
        $cancellationsCost = 0.0;
        $menuSections      = [];

        foreach ($ordersArray as $order) {
            $sales += $order->getPrice();

            if ($order->getTable() === null) {
                $salesTakeAway += $order->getPrice();
            } elseif (!$order->isDrinksOnly()) {
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
                if ($recipe !== null && $recipe->getYield() > 0) {
                    $foodCost += $orderEntry->getQuantity() * ($recipe->getFoodCost($end) / $recipe->getYield());
                }

                $discountsTotal += $orderEntry->getDiscount();

                $cancellationCount = count($orderEntry->getOrderEntryCancellations());
                if ($cancellationCount > 0) {
                    // Per-unit price matching OrderEntry::getPrice()'s internal
                    // formula (menu price → weight adjustment → + extras), but
                    // without the line-level discount (discount applies to the
                    // items kept, not to the cancelled ones).
                    $unit = $orderEntry->getMenuItemPrice();
                    $weight = $orderEntry->getWeight();
                    if ($weight !== null) {
                        $unit *= $weight / 1000;
                    }
                    foreach ($orderEntry->getOrderEntryExtras() as $extra) {
                        $unit += $extra->getPrice();
                    }
                    $cancellationsCost += $unit * $cancellationCount;
                }
            }
        }

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
            'sales'             => $sales,
            'salesTakeAway'     => $salesTakeAway,
            'coversAdults'      => $coversAdults,
            'coversMinors'      => $coversMinors,
            'totalWeight'       => $totalWeight,
            'foodCost'          => $foodCost,
            'discountsTotal'    => $discountsTotal,
            'cancellationsCost' => $cancellationsCost,
            'menuSections'      => $menuSections,
            'servedPlates'      => $servedPlates,
            'servedDrinks'      => $servedDrinks,
        ];
    }
}
