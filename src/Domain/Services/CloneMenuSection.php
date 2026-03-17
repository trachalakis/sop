<?php

declare(strict_types=1);

namespace Domain\Services;

use Domain\Entities\Menu;
use Domain\Entities\MenuSection;
use Domain\Entities\MenuSectionTranslation;
use Domain\Entities\Extra;
use Doctrine\Common\Collections\ArrayCollection;

final class CloneMenuSection
{
    public function __construct(
        private CloneMenuItem $cloneMenuItem
    ) {}

    public function __invoke(
        MenuSection $menuSection,
        Menu $targetMenu
    ): MenuSection
    {
        $clone = new MenuSection;

        $clone->setCustomFields($menuSection->getCustomFields());
        $clone->setIsActive($menuSection->getIsActive());
        $clone->setIsPublic($menuSection->getIsPublic());
        $clone->setMenu($targetMenu);
        $clone->setPosition($menuSection->getPosition());
        $clone->setPrintMenuPage($menuSection->getPrintMenuPage());

        // Clone translations
        $translations = new ArrayCollection;
        foreach ($menuSection->getTranslations() as $translation) {
            $clonedTranslation = new MenuSectionTranslation;
            $clonedTranslation->setLanguage($translation->getLanguage());
            $clonedTranslation->setMenuSection($clone);
            $clonedTranslation->setName($translation->getName());
            $translations[] = $clonedTranslation;
        }
        $clone->setTranslations($translations);

        
        $extras = new ArrayCollection;
        foreach ($menuSection->getExtras() as $extra) {
            $clonedExtra = new Extra(
                $extra->getName(),
                $extra->getPrice(),
                null,
                $clone
            );
            $extras[] = $clonedExtra;
        }
        $clone->setExtras($extras);

        // Clone menu items
        $menuItems = new ArrayCollection;
        foreach ($menuSection->getMenuItems() as $menuItem) {
            if ($menuItem->getIsArchived()) {
                continue;
            }
            $clonedMenuItem = ($this->cloneMenuItem)($menuItem);
            $clonedMenuItem->setMenuSection($clone);
            $menuItems[] = $clonedMenuItem;
        }
        $clone->setMenuItems($menuItems);

        return $clone;
    }
}