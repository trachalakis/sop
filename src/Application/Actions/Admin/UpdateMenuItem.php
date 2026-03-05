<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Domain\Entities\Extra;
use Domain\Entities\MenuItemTranslation;
use Domain\Repositories\LanguagesRepository;
use Domain\Repositories\MenuSectionsRepository;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\StationsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateMenuItem
{
    private LanguagesRepository $languagesRepository;

    private MenuItemsRepository $menuItemsRepository;

    private MenuSectionsRepository $menuSectionsRepository;

    private StationsRepository $stationsRepository;

    private Twig $twig;

    public function __construct(
        LanguagesRepository $languagesRepository,
        MenuSectionsRepository $menuSectionsRepository,
        MenuItemsRepository $menuItemsRepository,
        StationsRepository $stationsRepository,
        Twig $twig
    ) {
        $this->languagesRepository = $languagesRepository;
        $this->menuSectionsRepository = $menuSectionsRepository;
        $this->menuItemsRepository = $menuItemsRepository;
        $this->stationsRepository = $stationsRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$languages = $this->languagesRepository->findAll();
    	$menuItem = $this->menuItemsRepository->findOneBy(['id' => $request->getQueryParams()['id']]);
        //dd($menuItem->getExtras());
        if ($request->getMethod() == 'POST') {
    		$postData = $request->getParsedBody();

            if (isset($postData['availableQuantity'])) {
                $menuItem->setAvailableQuantity(intval($postData['availableQuantity']));

                //TODO, duplicate code
                $this->menuItemsRepository->persist($menuItem);

                if (function_exists('apcu_clear_cache')) {
                    apcu_clear_cache();
                }

                return $response->withHeader('Location', '/admin/menu')->withStatus(302);
            }

            $menuItem->setPrice(floatval($postData['price']));
            $menuItem->setIsPricePerKg(boolval($postData['isPricePerKg']));
            
            $menuItem->setIsActive(boolval($postData['isActive']));
            $menuItem->setIsArchived(boolval($postData['isArchived']));
            if ($menuItem->getIsArchived()) {
                $menuItem->setIsActive(false);
            }
            $menuItem->setPosition(intval($postData['position']));
            $menuItem->setIsPublic(boolval($postData['isPublic']));
            $menuItem->setIsDrink(boolval($postData['isDrink']));
            $menuItem->setTrackAvailableQuantity(boolval($postData['trackAvailableQuantity']));

            $menuSection = $this->menuSectionsRepository->findOneBy(['id' => $postData['menuSection']]);
            $menuItem->setMenuSection($menuSection);

            $menuItem->setStations(new ArrayCollection);
            if (isset($postData['stations'])) {
                $menuItem->setStations($this->stationsRepository->findBy(['id' => $postData['stations']]));
            }

            $extras = new ArrayCollection;
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
            	$menuItemTranslation->setName(trim($postData['translations'][$language->getid()]['name']));

            	$translations[] = $menuItemTranslation;
            }
            $menuItem->setTranslations($translations);

            $customFields = [];
            if (isset($postData['customFields'])) {
                foreach ($postData['customFields'] as $customField) {
                	$customFields[$customField['field']] = $customField['value'];
                }
            }
            $menuItem->setCustomFields($customFields);

            
            
            
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
        $stations = $this->stationsRepository->findBy([], ['name' => 'asc']);
    	return $this->twig->render(
            $response,
            'admin/update_menu_item.twig',
            [
            	'languages' => $languages,
            	'menuItem' => $menuItem,
            	'menuSections' => $menuSections,
            	'stations' => $stations
            ]
        );
    }
}