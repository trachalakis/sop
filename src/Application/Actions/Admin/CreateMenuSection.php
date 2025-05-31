<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\MenuSection;
use Domain\Entities\MenuSectionTranslation;
use Domain\Repositories\LanguagesRepositoryInterface;
use Domain\Repositories\MenusRepositoryInterface;
use Domain\Repositories\MenuSectionsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateMenuSection
{
	private Twig $twig;

    private MenusRepositoryInterface $menusRepository;
    
    private MenuSectionsRepositoryInterface $menuSectionsRepository;

    private LanguagesRepositoryInterface $languagesRepository;

    public function __construct(
    	Twig $twig,
    	MenusRepositoryInterface $menusRepository,
        MenuSectionsRepositoryInterface $menuSectionsRepository,
    	LanguagesRepositoryInterface $languagesRepository
    ) {
        $this->twig = $twig;
        $this->menusRepository = $menusRepository;
        $this->menuSectionsRepository = $menuSectionsRepository;
        $this->languagesRepository = $languagesRepository;
    }

    public function __invoke(Request $request, Response $response)
	{
		$languages = $this->languagesRepository->findAll();
        $menus = $this->menusRepository->findBy(
            [], ['isActive' => 'desc', 'createdAt' => 'desc']
        );
        $menu = $this->menusRepository->findOneBy([
            'id' => $request->getQueryParams()['menu']
        ]);

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
                'menus' => $menus,
                'selectedMenu' => $selectedMenu
            ]
        );
	}
}