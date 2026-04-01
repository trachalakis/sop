<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Extra;
use Domain\Entities\MenuSection;
use Domain\Entities\MenuSectionTranslation;
use Domain\Repositories\LanguagesRepository;
use Domain\Repositories\MenusRepository;
use Domain\Repositories\MenuSectionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateMenuSection
{
    public function __construct(
    	private Twig $twig,
    	private MenusRepository $menusRepository,
        private MenuSectionsRepository $menuSectionsRepository,
    	private LanguagesRepository $languagesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
	{
		$languages = $this->languagesRepository->findAll();
        $menu = $this->menusRepository->find($request->getQueryParams()['menu']);

		if ($request->getMethod() == 'POST') {
            $postData = $request->getParsedBody();

            $menuSection = new MenuSection;
            $menuSection->setIsActive(boolval($postData['isActive']));
            $menuSection->setIsPublic(boolval($postData['isPublic']));
            $menuSection->setPosition(intval($postData['position']));
            $menuSection->setPrintMenuPage(intval($postData['printMenuPage']));
            $menuSection->setMenu($menu);

            $translations = [];
            foreach($languages as $language) {
            	$menuSectionTranslation = new MenuSectionTranslation;
            	$menuSectionTranslation->setLanguage($language);
            	$menuSectionTranslation->setMenuSection($menuSection);
            	$menuSectionTranslation->setName($postData['translations'][$language->getid()]['name']);

            	$translations[] = $menuSectionTranslation;
            }
            $menuSection->setTranslations($translations);

            $this->menuSectionsRepository->persist($menuSection);

            if (function_exists('apcu_clear_cache')) {
            	apcu_clear_cache();
            }

            return $response->withHeader('Location', '/admin/menu?id=' . $menu->getId())->withStatus(302);
        }

        return $this->twig->render(
            $response,
            'admin/create_menu_section.twig',
            [
                'languages' => $languages, 
                'menu' => $menu,

            ]
        );
	}
}