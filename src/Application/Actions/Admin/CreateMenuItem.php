<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\MenuItem;
use Domain\Entities\MenuItemTranslation;
use Domain\Enums\PriceUnit;
use Domain\Repositories\LanguagesRepository;
use Domain\Repositories\MenusRepository;
use Domain\Repositories\MenuSectionsRepository;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\PrintersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateMenuItem
{
    private LanguagesRepository $languagesRepository;

    private MenusRepository $menusRepository;

    private MenuItemsRepository $menuItemsRepository;

    private MenuSectionsRepository $menuSectionsRepository;

    private PrintersRepository $printersRepository;

    private Twig $twig;

    public function __construct(
        LanguagesRepository $languagesRepository,
        MenusRepository $menusRepository,
        MenuSectionsRepository $menuSectionsRepository,
        MenuItemsRepository $menuItemsRepository,
        PrintersRepository $printersRepository,
        Twig $twig
    ) {
        $this->languagesRepository = $languagesRepository;
        $this->menusRepository = $menusRepository;
        $this->menuSectionsRepository = $menuSectionsRepository;
        $this->menuItemsRepository = $menuItemsRepository;
        $this->printersRepository = $printersRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$languages = $this->languagesRepository->findAll();
        $menu = $this->menusRepository->find($request->getQueryParams()['menu']);

    	if ($request->getMethod() == 'POST') {
    		$requestData = $request->getParsedBody();

            $menuItem = new MenuItem;
            $menuItem->setPrice(floatval($requestData['price']));
            $menuItem->setPriceUnit(PriceUnit::from($requestData['priceUnit']));
            $menuItem->setIsActive(boolval($requestData['isActive']));
            $menuItem->setIsArchived(false);
            $menuItem->setPosition(intval($requestData['position']));
            $menuItem->setIsDrink(boolval($requestData['isDrink']));
            $menuItem->setTrackAvailableQuantity(false);
            $menuItem->setAvailableQuantity(60);
            
            $menuSection = $this->menuSectionsRepository->find($requestData['menuSection']);
            $menuItem->setMenuSection($menuSection);

            $menuItem->setPrinters([]);
            if (isset($requestData['printers'])) {
                $menuItem->setPrinters($this->printersRepository->findBy(['id' => $requestData['printers']]));
            }

            $translations = [];
            foreach($languages as $language) {
            	$menuItemTranslation = new MenuItemTranslation;
            	$menuItemTranslation->setLanguage($language);
            	$menuItemTranslation->setMenuItem($menuItem);
            	$menuItemTranslation->setName(trim($requestData['translations'][$language->getid()]['name']));

            	$translations[] = $menuItemTranslation;
            }
            $menuItem->setTranslations($translations);

            $this->menuItemsRepository->persist($menuItem);

			if (function_exists('apcu_clear_cache')) {
            	apcu_clear_cache();
            }

            return $response->withHeader('Location', '/admin/menu?id=' . $menu->getId())->withStatus(302);
    	}
        
    	$menuSections = $this->menuSectionsRepository->findBy(['menu' => $menu], ['position' => 'asc']);
        $printers = $this->printersRepository->findBy([], ['name' => 'asc']);
    	return $this->twig->render(
            $response,
            'admin/create_menu_item.twig',
            [
                'menu' => $menu,
                'menuSections' => $menuSections,
                'printers' => $printers,
                'languages' => $languages
            ]
        );
    }
}