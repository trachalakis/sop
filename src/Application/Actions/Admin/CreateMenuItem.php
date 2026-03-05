<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Extra;
use Domain\Entities\MenuItem;
use Domain\Entities\MenuItemTranslation;
use Domain\Repositories\LanguagesRepository;
use Domain\Repositories\MenusRepository;
use Domain\Repositories\MenuSectionsRepository;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\StationsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateMenuItem
{
    private LanguagesRepository $languagesRepository;

    private MenusRepository $menusRepository;
    
    private MenuItemsRepository $menuItemsRepository;

    private MenuSectionsRepository $menuSectionsRepository;


    private StationsRepository $stationsRepository;

    private Twig $twig;

    public function __construct(
        LanguagesRepository $languagesRepository,
        MenusRepository $menusRepository,
        MenuSectionsRepository $menuSectionsRepository,
        MenuItemsRepository $menuItemsRepository,
        StationsRepository $stationsRepository,
        Twig $twig
    ) {
        $this->languagesRepository = $languagesRepository;
        $this->menusRepository = $menusRepository;
        $this->menuSectionsRepository = $menuSectionsRepository;
        $this->menuItemsRepository = $menuItemsRepository;
        $this->stationsRepository = $stationsRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$languages = $this->languagesRepository->findAll();
        $menu = $this->menusRepository->findOneBy(['id' => $request->getQueryParams()['menu']]);

    	if ($request->getMethod() == 'POST') {
    		$postData = $request->getParsedBody();

            $menuItem = new MenuItem;
            $menuItem->setPrice(floatval($postData['price']));
            $menuItem->setIsPricePerKg(boolval($postData['isPricePerKg']));
            
            $menuItem->setIsActive(boolval($postData['isActive']));
            $menuItem->setIsArchived(false);
            $menuItem->setPosition(intval($postData['position']));
            $menuItem->setIsPublic(boolval($postData['isPublic']));
            
            $menuItem->setIsDrink(boolval($postData['isDrink']));
            $menuItem->setTrackAvailableQuantity(false);
            $menuItem->setAvailableQuantity(60);
            $menuSection = $this->menuSectionsRepository->findOneBy(['id' => $postData['menuSection']]);
            $menuItem->setMenuSection($menuSection);


            $menuItem->setStations([]);
            if (isset($postData['stations'])) {
                $menuItem->setStations($this->stationsRepository->findBy(['id' => $postData['stations']]));
            }

            $translations = [];
            foreach($languages as $language) {
            	$menuItemTranslation = new MenuItemTranslation;
            	$menuItemTranslation->setLanguage($language);
            	$menuItemTranslation->setMenuItem($menuItem);
            	$menuItemTranslation->setName(trim($postData['translations'][$language->getid()]['name']));

            	$translations[] = $menuItemTranslation;
            }
            $menuItem->setTranslations($translations);

            if (isset($postData['extra'])) {
                foreach ($postData['extra'] as $extra) {
                    $name = trim($extra['name']);
                    if (strlen($name) > 0) {
                        $extra = new Extra(
                            $name,
                            floatval($extra['price']),
                            $menuItem,
                            null
                        );

                        $menuItem->addExtra($extra);
                    }
                }
            }

            $this->menuItemsRepository->persist($menuItem);

			if (function_exists('apcu_clear_cache')) {
            	apcu_clear_cache();
            }

            return $response->withHeader('Location', '/admin/menu?id=' . $menu->getId())->withStatus(302);
    	}

        
    	$menuSections = $this->menuSectionsRepository->findBy(['menu' => $menu], ['position' => 'asc']);
        $stations = $this->stationsRepository->findBy([], ['name' => 'asc']);
    	return $this->twig->render(
            $response,
            'admin/create_menu_item.twig',
            [
                'menuSections' => $menuSections,
                'stations' => $stations,
                'languages' => $languages
            ]
        );
    }
}