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
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateMenuItem
{
    private LanguagesRepository $languagesRepository;

    private MenuItemsRepository $menuItemsRepository;

    private MenuSectionsRepository $menuSectionsRepository;

    private PrintersRepository $printersRepository;

    private Twig $twig;

    public function __construct(
        LanguagesRepository $languagesRepository,
        MenuSectionsRepository $menuSectionsRepository,
        MenuItemsRepository $menuItemsRepository,
        PrintersRepository $printersRepository,
        Twig $twig
    ) {
        $this->languagesRepository = $languagesRepository;
        $this->menuSectionsRepository = $menuSectionsRepository;
        $this->menuItemsRepository = $menuItemsRepository;
        $this->printersRepository = $printersRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$languages = $this->languagesRepository->findAll();
    	$menuItem = $this->menuItemsRepository->findOneBy(['id' => $request->getQueryParams()['id']]);
        //dd($menuItem->getExtras());
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
            $menuItem->setIsArchived(boolval($requestData['isArchived']));
            if ($menuItem->getIsArchived()) {
                $menuItem->setIsActive(false);
            }
            $menuItem->setPosition(intval($requestData['position']));
            $menuItem->setIsDrink(boolval($requestData['isDrink']));
            $menuItem->setTrackAvailableQuantity(boolval($requestData['trackAvailableQuantity']));

            $menuSection = $this->menuSectionsRepository->findOneBy(['id' => $requestData['menuSection']]);
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
            
            $customFields = [];
            if (isset($requestData['customFields'])) {
                foreach ($requestData['customFields'] as $customField) {
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
        $printers = $this->printersRepository->findBy([], ['name' => 'asc']);
    	return $this->twig->render(
            $response,
            'admin/update_menu_item.twig',
            [
            	'languages' => $languages,
            	'menuItem' => $menuItem,
            	'menuSections' => $menuSections,
            	'printers' => $printers
            ]
        );
    }
}