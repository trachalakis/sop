<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\MenuItem;
use Domain\Entities\MenuItemExtra;
use Domain\Entities\MenuItemPrice;
use Domain\Entities\MenuItemTranslation;
use Domain\Entities\MenuSection;
use Domain\Repositories\LanguagesRepositoryInterface;
use Domain\Repositories\MenuSectionsRepositoryInterface;
use Domain\Repositories\MenuItemsRepositoryInterface;
use Domain\Repositories\PriceListsRepositoryInterface;
use Domain\Repositories\StationsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CloneMenuItem
{
    private LanguagesRepositoryInterface $languagesRepository;

    private MenuItemsRepositoryInterface $menuItemsRepository;

    private MenuSectionsRepositoryInterface $menuSectionsRepository;

    private PriceListsRepositoryInterface $priceListsRepository;

    private StationsRepositoryInterface $stationsRepository;

    private Twig $twig;

    public function __construct(
        LanguagesRepositoryInterface $languagesRepository,
        MenuSectionsRepositoryInterface $menuSectionsRepository,
        MenuItemsRepositoryInterface $menuItemsRepository,
        PriceListsRepositoryInterface $priceListsRepository,
        StationsRepositoryInterface $stationsRepository,
        Twig $twig
    ) {
        $this->languagesRepository = $languagesRepository;
        $this->menuSectionsRepository = $menuSectionsRepository;
        $this->menuItemsRepository = $menuItemsRepository;
        $this->priceListsRepository = $priceListsRepository;
        $this->stationsRepository = $stationsRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$languages = $this->languagesRepository->findAll();
        $menuItem = $this->menuItemsRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

        $clone = clone $menuItem;

        $translations = [];
        foreach($languages as $language) {
            $menuItemTranslation = new MenuItemTranslation;
            $menuItemTranslation->setLanguage($language);
            $menuItemTranslation->setMenuItem($clone);
            $menuItemTranslation->setName($menuItem->getTranslation($language->getIsoCode())->getName());
            $menuItemTranslation->setWebsiteDescription($menuItem->getTranslation($language->getIsoCode())->getWebsiteDescription());

            $translations[] = $menuItemTranslation;
        }
        $clone->setTranslations($translations);

        $this->menuItemsRepository->persist($clone);

        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        return $response->withHeader('Location', '/admin/menu')->withStatus(302);

        /*$languages = $this->languagesRepository->findAll();

    	if ($request->getMethod() == 'POST') {
    		$postData = $request->getParsedBody();

            $menuItem = new MenuItem;
            $menuItem->setPrice(floatval($postData['price']));
            $menuItem->setIsPricePerKg(boolval($postData['isPricePerKg']));
            $menuItem->setDescription(trim($postData['description']));
            $menuItem->setIsActive(boolval($postData['isActive']));
            $menuItem->setIsArchived(false);
            $menuItem->setPosition(intval($postData['position']));
            $menuItem->setIsPublic(boolval($postData['isPublic']));
            $menuItem->setIsFeatured(boolval($postData['isFeatured']));
            $menuItem->setIsDrink(boolval($postData['isDrink']));
            $menuItem->setTrackAvailableQuantity(false);
            $menuItem->setAvailableQuantity(60);
            $menuSection = $this->menuSectionsRepository->findOneBy(['id' => $postData['menuSection']]);
            $menuItem->setMenuSection($menuSection);

            $photo = $request->getUploadedFiles()['photo'];
            if ($photo->getError() === UPLOAD_ERR_OK) {
	            $target = "/var/www/public/photos/" . $photo->getClientFileName();
	            $photo->moveTo($target);
	            $menuItem->setPhoto($target);
	        }

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
            	$menuItemTranslation->setWebsiteDescription(trim($postData['translations'][$language->getid()]['websiteDescription']));

            	$translations[] = $menuItemTranslation;
            }
            $menuItem->setTranslations($translations);

            if (isset($postData['menuItemPrice'])) {
                foreach ($postData['menuItemPrice'] as $menuItemPrice) {
                    if(!empty($menuItemPrice['price'])) {
                        $priceList = $this->priceListsRepository->findOneBy(['id' => $menuItemPrice['priceList']]);

                        $menuItemPrice = new MenuItemPrice(
                            $menuItemPrice['price'],
                            $priceList,
                            $menuItem
                        );

                        $menuItem->addaddMenuItemPrice($menuItemPrice);
                    }
                }
            }

            if (isset($postData['menuItemExtra'])) {
                foreach ($postData['menuItemExtra'] as $extra) {
                    $name = trim($extra['name']);
                    if (strlen($name) > 0) {
                        $menuItemExtra = new MenuItemExtra(
                            $name,
                            floatval($extra['price']),
                            $menuItem
                        );

                        $menuItem->addMenuItemExtra($menuItemExtra);
                    }
                }
            }

            $this->menuItemsRepository->persist($menuItem);

			if (function_exists('apcu_clear_cache')) {
            	apcu_clear_cache();
            }

            return $response->withHeader('Location', '/admin/menu')->withStatus(302);*/
    	//}

    	$menuSections = $this->menuSectionsRepository->findBy([], ['position' => 'asc']);
        $stations = $this->stationsRepository->findBy([], ['name' => 'asc']);
        $priceLists = $this->priceListsRepository->findBy([], ['name' => 'asc']);
    	return $this->twig->render(
            $response,
            'admin/create_menu_item.twig',
            [
                'menuSections' => $menuSections,
                'stations' => $stations,
                'languages' => $languages,
                'priceLists' => $priceLists
            ]
        );
    }
}