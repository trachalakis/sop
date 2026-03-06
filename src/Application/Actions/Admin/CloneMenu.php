<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTimeImmutable;
use Domain\Entities\Menu;
use Domain\Entities\MenuSection;
use Domain\Entities\MenuItem;
use Domain\Entities\MenuItemExtra;
use Domain\Entities\MenuItemTranslation;
use Domain\Repositories\MenusRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CloneMenu
{
	private MenusRepository $menusRepository;

	private Twig $twig;

    public function __construct(
        MenusRepository $menusRepository,
        Twig $twig
    ) {
        $this->twig = $twig;
        $this->menusRepository = $menusRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $menu = $this->menusRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

        //$clonedMenu = clone $menu;
        $clonedMenu = new Menu;
        $clonedMenu->setCreatedAt(new DateTimeImmutable);
        $clonedMenu->setIsActive(false);
        $clonedMenu->setName('Cloned ' . $menu->getName());

        foreach($menu->getMenuSections() as $menuSection) {
            $clonedSection = new MenuSection;
            $clonedSection->setMenu($clonedMenu);
            $clonedSection->setIsActive($menuSection->getIsActive());
            $clonedSection->setIsPublic($menuSection->getIsPublic());
            $clonedSection->setPosition($menuSection->getPosition());
            $clonedSection->setPrintMenuPage($menuSection->getPrintMenuPage());

            foreach($menuSection->getMenuItems() as $menuItem) {
                if ($menuItem->getIsArchived()) {
                    continue;
                }
                $clonedItem = new MenuItem;

                $clonedItem->setAvailableQuantity(null);
                $clonedItem->setCustomFields($menuItem->getCustomFields());
                $clonedItem->setDescription($menuItem->getDescription());
                $clonedItem->setIsActive($menuItem->getIsActive());
                $clonedItem->setIsArchived($menuItem->getIsArchived());
                $clonedItem->setIsDrink($menuItem->getIsDrink());
                $clonedItem->setIsFeatured($menuItem->getIsFeatured());
                $clonedItem->setIsPricePerKg($menuItem->getIsPricePerKg());
                $clonedItem->setIsPublic($menuItem->getIsPublic());
                $clonedItem->setKotName($menuItem->getKotName());
                $clonedItem->setMenuSection($clonedSection);

                //extras
                foreach($menuItem->getMenuItemExtras() as $menuItemExtra) {
                    $clonedExtra = new MenuItemExtra(
                        $menuItemExtra->getName(),
                        $menuItemExtra->getPrice(),
                        $clonedItem
                    );

                    $clonedItem->getMenuItemExtras()->add($clonedExtra);
                }

                $clonedItem->setPhoto($menuItem->getPhoto());
                $clonedItem->setPosition($menuItem->getPosition());
                $clonedItem->setPrice($menuItem->getPrice());
                $clonedItem->setPrinters($menuItem->getPrinters());
                $clonedItem->setTrackAvailableQuantity($menuItem->getTrackAvailableQuantity());

                foreach($menuItem->getTranslations() as $translation) {
                    $clonedTranslation = new MenuItemTranslation;

                    $clonedTranslation->setLanguage($translation->getLanguage());
                    $clonedTranslation->setMenuItem($menuItem);
                    $clonedTranslation->setName($translation->getName());
                    $clonedTranslation->setWebsiteDescription($translation->getWebsiteDescription());
                
                    $clonedItem->getTranslations()->add($clonedTranslation);
                }
            
                $clonedSection->getMenuItems()->add($clonedItem);
            }

            $clonedMenu->getMenuSections()->add($clonedSection);
        }
        


        $this->menusRepository->persist($clonedMenu);

        //return $this->twig->render($response, 'admin/menu.twig', ['menus' => $menus]);
        return $response->withHeader('Location', '/admin/menu')->withStatus(302);
    }
}