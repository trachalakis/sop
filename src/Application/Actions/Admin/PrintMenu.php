<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenuSectionsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class PrintMenu
{
    private Twig $twig;

    private MenuSectionsRepositoryInterface $menuSectionsRepository;

    public function __construct(Twig $twig, MenuSectionsRepositoryInterface $menuSectionsRepository)
    {
        $this->twig = $twig;
        $this->menuSectionsRepository = $menuSectionsRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $requestParams = $request->getQueryparams();

        if (isset($requestParams['page'])) {
        	$menuSections = $this->menuSectionsRepository->findBy(
	        	['isActive' => true, 'printMenuPage' => $requestParams['page']],
	        	['position' => 'asc']
	        );
	    } else {
	    	$menuSections = $this->menuSectionsRepository->findBy(
	        	['isActive' => true],
	        	['position' => 'asc']
	        );
	    }

        return $this->twig->render(
        	$response,
        	sprintf('admin/print_%s.twig', $requestParams['template'] ?? 'menu'),
        	[
        		'lang' => $requestParams['lang'] ?? 'en',
        		'menuSections' => $menuSections
        	]
        );
    }
}