<?php

declare(strict_types=1);

namespace Domain\Services;

use Domain\Entities\MenuItem;
use Domain\Entities\MenuItemTranslation;
use Domain\Entities\Extra;
use Domain\Entities\MenuItemIngredient;
use Domain\Enums\PriceUnit;
use Doctrine\Common\Collections\ArrayCollection;

final class CloneMenuItem
{
    public function __invoke(MenuItem $menuItem): MenuItem
    {
        $clone = new MenuItem;

        $clone->setAvailableQuantity(null);
        $clone->setCustomFields($menuItem->getCustomFields());
        $clone->setIsActive($menuItem->getIsActive());
        $clone->setIsArchived(false); // Cloned items are not archived
        $clone->setIsDrink($menuItem->getIsDrink());
        $clone->setMenuSection($menuItem->getMenuSection());
        $clone->setPosition($menuItem->getPosition());
        $clone->setPrice($menuItem->getPrice());
        $clone->setPriceUnit(PriceUnit::from($menuItem->getPriceUnit()));
        $clone->setPrinters($menuItem->getPrinters());
        $clone->setTrackAvailableQuantity($menuItem->getTrackAvailableQuantity());

        // Clone translations
        $translations = [];
        foreach ($menuItem->getTranslations() as $translation) {
            $clonedTranslation = new MenuItemTranslation;
            $clonedTranslation->setLanguage($translation->getLanguage());
            $clonedTranslation->setMenuItem($clone);
            $clonedTranslation->setName($translation->getName());
            $translations[] = $clonedTranslation;
        }
        $clone->setTranslations($translations);

        // Clone extras
        $extras = new ArrayCollection;
        foreach ($menuItem->getExtras() as $extra) {
            $clonedExtra = new Extra(
                $extra->getName(),
                $extra->getPrice(),
                $clone,
                null
            );
            $extras[] = $clonedExtra;
        }
        $clone->setExtras($extras);

        // Clone ingredients
        $ingredients = new ArrayCollection;
        foreach ($menuItem->getIngredients() as $ingredient) {
            $clonedIngredient = new MenuItemIngredient;
            $clonedIngredient->setSupply($ingredient->getSupply());
            $clonedIngredient->setQuantity($ingredient->getQuantity());
            $clonedIngredient->setMenuItem($clone);
            $ingredients[] = $clonedIngredient;
        }
        $clone->setIngredients($ingredients);

        return $clone;
    }
}