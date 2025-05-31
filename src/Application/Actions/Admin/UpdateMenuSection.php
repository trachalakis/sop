<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\MenuSection;
use Domain\Entities\MenuSectionTranslation;
use Domain\Repositories\MenuSectionsRepositoryInterface;
use Domain\Repositories\LanguagesRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateMenuSection
{
    private Twig $twig;

    private MenuSectionsRepositoryInterface $menuSectionsRepository;

    private LanguagesRepositoryInterface $languagesRepository;

    public function __construct(
    	Twig $twig,
    	MenuSectionsRepositoryInterface $menuSectionsRepository,
    	LanguagesRepositoryInterface $languagesRepository
    ) {
        $this->twig = $twig;
        $this->menuSectionsRepository = $menuSectionsRepository;
        $this->languagesRepository = $languagesRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$languages = $this->languagesRepository->findAll();
    	$menuSection = $this->menuSectionsRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

    	if ($request->getMethod() == 'POST') {
    		$postData = $request->getParsedBody();

            $menuSection->setIsActive(boolval($postData['isActive']));
            $menuSection->setIsPublic(boolval($postData['isPublic']));
            $menuSection->setPosition(intval($postData['position']));
            $menuSection->setPrintMenuPage(intval($postData['printMenuPage']));

            $translations = [];
            foreach($languages as $language) {
            	$menuSectionTranslation = new MenuSectionTranslation;
            	$menuSectionTranslation->setLanguage($language);
            	$menuSectionTranslation->setMenuSection($menuSection);
            	$menuSectionTranslation->setName($postData['translations'][$language->getId()]['name']);

            	$translations[] = $menuSectionTranslation;
            }
            $menuSection->setTranslations($translations);

            $this->menuSectionsRepository->persist($menuSection);

            if (function_exists('apcu_clear_cache')) {
            	apcu_clear_cache();
            }

            return $response->withHeader('Location', '/admin/menu?id=' . $menuSection->getMenu()->getId())->withStatus(302);
    	}

    	return $this->twig->render(
            $response,
            'admin/update_menu_section.twig',
            [
            	'menuSection' => $menuSection,
            	'languages' => $languages
            ]
        );
    }
}