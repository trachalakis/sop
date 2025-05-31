<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Domain\Entities\MenuItemTranslation;
use Domain\Repositories\LanguagesRepositoryInterface;
use Domain\Repositories\MenuSectionsRepositoryInterface;
use Domain\Repositories\MenuItemsRepositoryInterface;
use Domain\Repositories\PriceListsRepositoryInterface;
use Domain\Repositories\StationsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateMenuItem
{
    private LanguagesRepositoryInterface $languagesRepository;

    private MenuItemsRepositoryInterface $menuItemsRepository;

    private MenuSectionsRepositoryInterface $menuSectionsRepository;

    private StationsRepositoryInterface $stationsRepository;

    private Twig $twig;

    public function __construct(
        LanguagesRepositoryInterface $languagesRepository,
        MenuSectionsRepositoryInterface $menuSectionsRepository,
        MenuItemsRepositoryInterface $menuItemsRepository,
        StationsRepositoryInterface $stationsRepository,
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
                //dd($this->stationsRepository->findBy(['id' => $postData['stations']]));
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