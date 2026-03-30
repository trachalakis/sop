<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Domain\Enums\PriceUnit;
use Domain\Entities\Extra;
use Domain\Entities\MenuItemTranslation;
use Domain\Repositories\LanguagesRepository;
use Domain\Repositories\MenuSectionsRepository;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\PrintersRepository;
use Domain\Repositories\RecipesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateMenuItem
{
    private LanguagesRepository $languagesRepository;

    private MenuItemsRepository $menuItemsRepository;

    private MenuSectionsRepository $menuSectionsRepository;

    private PrintersRepository $printersRepository;

    private RecipesRepository $recipesRepository;

    private Twig $twig;

    public function __construct(
        LanguagesRepository $languagesRepository,
        MenuSectionsRepository $menuSectionsRepository,
        MenuItemsRepository $menuItemsRepository,
        PrintersRepository $printersRepository,
        RecipesRepository $recipesRepository,
        Twig $twig
    ) {
        $this->languagesRepository = $languagesRepository;
        $this->menuSectionsRepository = $menuSectionsRepository;
        $this->menuItemsRepository = $menuItemsRepository;
        $this->printersRepository = $printersRepository;
        $this->recipesRepository = $recipesRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$languages = $this->languagesRepository->findAll();
    	$menuItem = $this->menuItemsRepository->find($request->getQueryParams()['id']);
        
        if ($request->getMethod() == 'POST') {
    		$requestData = $request->getParsedBody();

            if (isset($requestData['availableQuantity'])) {
                $menuItem->setAvailableQuantity(intval($requestData['availableQuantity']));

                //TODO, duplicate code
                $this->menuItemsRepository->persist($menuItem);

                if (function_exists('apcu_clear_cache')) {
                    apcu_clear_cache();
                }

                return $response->withHeader('Location', '/admin/menu')->withStatus(302);
            }

            $menuItem->setPrice(floatval($requestData['price']));
            $menuItem->setPriceUnit(PriceUnit::from($requestData['priceUnit']));
            
            $menuItem->setIsActive(boolval($requestData['isActive']));
            $menuItem->setPosition(intval($requestData['position']));
            $menuItem->setIsDrink(boolval($requestData['isDrink']));
            $menuItem->setTrackAvailableQuantity(boolval($requestData['trackAvailableQuantity']));

            $menuSection = $this->menuSectionsRepository->find($requestData['menuSection']);
            $menuItem->setMenuSection($menuSection);

            $menuItem->setPrinters(new ArrayCollection);
            if (isset($requestData['printers'])) {
                $menuItem->setPrinters($this->printersRepository->findBy(['id' => $requestData['printers']]));
            }

            $extras = new ArrayCollection;
            if (isset($requestData['extra'])) {
                foreach ($requestData['extra'] as $extra) {
                    $name = trim($extra['name']);
                    if (strlen($name) > 0) {
                        $extra = new Extra(
                            $name,
                            floatval($extra['price']),
                            $menuItem,
                            null
                        );
                        $extras->add($extra);
                    }
                }
            }
            $menuItem->setExtras($extras);

            $translations = [];
            foreach($languages as $language) {
            	$menuItemTranslation = new MenuItemTranslation;
            	$menuItemTranslation->setLanguage($language);
            	$menuItemTranslation->setMenuItem($menuItem);
            	$menuItemTranslation->setName(trim($requestData['translations'][$language->getid()]['name']));

            	$translations[] = $menuItemTranslation;
            }
            $menuItem->setTranslations($translations);
            
            $menuItem->setCustomFields([]);
            if (isset($requestData['customFields'])) {
                foreach ($requestData['customFields'] as $customField) {
                    $menuItem->setCustomField($customField['field'], $customField['value']);
                }
            }
            
            $this->menuItemsRepository->persist($menuItem);

			if (function_exists('apcu_clear_cache')) {
            	apcu_clear_cache();
            }

            return $response->withHeader('Location', '/admin/menu?id=' . $menuItem->getMenuSection()->getMenu()->getId())->withStatus(302);
    	}
        
        $menuSections = $this->menuSectionsRepository->findBy(
            ['menu' => $menuItem->getMenuSection()->getMenu()],
            ['position' => 'asc']
        );
        $printers = $this->printersRepository->findBy([], ['name' => 'asc']);
        $recipe = $this->recipesRepository->findOneBy(['menuItem' => $menuItem]);
    	return $this->twig->render(
            $response,
            'admin/update_menu_item.twig',
            [
            	'languages' => $languages,
            	'menuItem' => $menuItem,
            	'menuSections' => $menuSections,
            	'printers' => $printers,
                'recipe' => $recipe,
            ]
        );
    }
}