<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenusRepository;
use Domain\Repositories\MenuSectionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class PrintMenu
{
    private Twig $twig;

    private MenusRepository $menusRepository;

    private MenuSectionsRepository $menuSectionsRepository;

    public function __construct(
        MenusRepository $menusRepository,
        MenuSectionsRepository $menuSectionsRepository,
        Twig $twig
    ) {
        
        $this->menusRepository = $menusRepository;
        $this->menuSectionsRepository = $menuSectionsRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
        $requestParams = $request->getQueryparams();

        $menu = $this->menusRepository->find($requestParams['menu']);
        
        if (isset($requestParams['page'])) {
        	$menuSections = $this->menuSectionsRepository->findBy(
	        	[
                    'isActive' => true,
                    'menu' => $menu            
                ],
	        	['position' => 'asc']
	        );
            $menuSections = array_filter($menuSections, function ($menuSection) use ($requestParams) {
                //var_dump($menuSection->getCustomField('print_menu_page') == $requestParams['page'] );
                return $menuSection->getCustomField('print_menu_page') == $requestParams['page'];
            });  
            //dd('1');  
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