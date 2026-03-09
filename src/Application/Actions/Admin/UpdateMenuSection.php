<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Domain\Entities\Extra;
use Domain\Entities\MenuSectionTranslation;
use Domain\Repositories\MenuSectionsRepository;
use Domain\Repositories\LanguagesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateMenuSection
{
    private Twig $twig;

    private MenuSectionsRepository $menuSectionsRepository;

    private LanguagesRepository $languagesRepository;

    public function __construct(
    	Twig $twig,
    	MenuSectionsRepository $menuSectionsRepository,
    	LanguagesRepository $languagesRepository
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
    		$requestData = $request->getParsedBody();

            $menuSection->setIsActive(boolval($requestData['isActive']));
            $menuSection->setPosition(intval($requestData['position']));

            $translations = [];
            foreach($languages as $language) {
            	$menuSectionTranslation = new MenuSectionTranslation;
            	$menuSectionTranslation->setLanguage($language);
            	$menuSectionTranslation->setMenuSection($menuSection);
            	$menuSectionTranslation->setName($requestData['translations'][$language->getId()]['name']);

            	$translations[] = $menuSectionTranslation;
            }
            $menuSection->setTranslations($translations);

            $extras = new ArrayCollection;
            if (isset($requestData['extra'])) {
                foreach ($requestData['extra'] as $extra) {
                    $name = trim($extra['name']);
                    if (strlen($name) > 0) {
                        $extra = new Extra(
                            $name,
                            floatval($extra['price']),
                            null,
                            $menuSection
                        );
                        $extras->add($extra);
                    }
                }
            }
            $menuSection->setExtras($extras);

            $customFields = [];
            if (isset($requestData['customFields'])) {
                foreach ($requestData['customFields'] as $customField) {
                    $customFields[$customField['field']] = $customField['value'];
                }
            }
            $menuSection->setCustomFields($customFields);
            
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