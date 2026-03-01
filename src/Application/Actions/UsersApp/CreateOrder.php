<?php

declare(strict_types=1);

namespace Application\Actions\UsersApp;

use Application\Actions\Admin\MenuSections;
use Datetime;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Domain\Repositories\MenuSectionsRepository;
use Domain\Repositories\OrdersRepository;
use Domain\Entities\Scan;
use Slim\Views\Twig;

final class CreateOrder
{
    private MenuSectionsRepository $menuSectionsRepository;
    
    private Twig $twig;

    public function __construct(
        MenuSectionsRepository $menuSectionsRepository,
        Twig $twig
    ) {
        $this->menuSectionsRepository = $menuSectionsRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
        $menuSections = $this->menuSectionsRepository->findBy(
            ['isActive' => true], ['position' => 'asc']
        );

        if ($request->getMethod() == 'POST') {
    		$postData = $request->getParsedBody();
        
            dd($postData);
        }

        return $this->twig->render(
            $response,
            'users_app/create_order.twig',
            [
                'user' => $_SESSION['user'],
                'menuSections' => $menuSections
            ]
        );
    }
}