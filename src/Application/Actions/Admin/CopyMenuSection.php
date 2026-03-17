<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\MenuItem;
use Domain\Entities\MenuItemExtra;
use Domain\Entities\MenuItemPrice;
use Domain\Entities\MenuItemTranslation;
use Domain\Entities\MenuSection;
use Domain\Repositories\LanguagesRepository;
use Domain\Repositories\MenusRepository;
use Domain\Repositories\MenuSectionsRepository;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\PrintersRepository;
use Domain\Services\CloneMenuSection;
use Domain\Services\CloneMenuItem;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CopyMenuSection
{
    private LanguagesRepository $languagesRepository;

    private MenuItemsRepository $menuItemsRepository;

    private MenuSectionsRepository $menuSectionsRepository;

    private MenusRepository $menusRepository;

    private PrintersRepository $printersRepository;

    private Twig $twig;

    public function __construct(
        LanguagesRepository $languagesRepository,
        MenuSectionsRepository $menuSectionsRepository,
        MenusRepository $menusRepository,
        //MenuItemsRepository $menuItemsRepository,
        PrintersRepository $printersRepository,
        Twig $twig
    ) {
        $this->languagesRepository = $languagesRepository;
        $this->menuSectionsRepository = $menuSectionsRepository;
        //$this->menuItemsRepository = $menuItemsRepository;
        $this->menusRepository = $menusRepository;
        $this->printersRepository = $printersRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
        $menuSection = $this->menuSectionsRepository->find($request->getQueryParams()['id']);
        $targetMenu = $this->menusRepository->find($request->getQueryParams()['target']);
        $clone = (new CloneMenuSection(new CloneMenuItem))($menuSection, $targetMenu);

        $this->menuSectionsRepository->persist($clone);

        /*$translations = [];
        foreach($languages as $language) {
            $menuSectionTranslation = new MenuItemTranslation;
            $menuItemTranslation->setLanguage($language);
            $menuItemTranslation->setMenuItem($clone);
            $menuItemTranslation->setName($menuItem->getTranslation($language->getIsoCode())->getName());
            $menuItemTranslation->setWebsiteDescription($menuItem->getTranslation($language->getIsoCode())->getWebsiteDescription());

            $translations[] = $menuItemTranslation;
        }
        $clone->setTranslations($translations);*/

        //

        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        return $response->withHeader('Location', '/admin/menu-sections/update?id=' . $clone->getId())->withStatus(302);

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

            $menuItem->setPrinters([]);
            if (isset($postData['stations'])) {
                $menuItem->setPrinters($this->printersRepository->findBy(['id' => $postData['stations']]));
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

    	/*return $this->twig->render(
            $response,
            'admin/create_menu_item.twig',
            [
                'menuSections' => $menuSections,
                'printers' => $printers,
                'languages' => $languages,
            ]
        );*/
    }
}