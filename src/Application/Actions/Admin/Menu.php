<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenuSectionsRepository;
use Domain\Repositories\MenusRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Menu
{
    private $twig;

    private $menusRepository;

    public function __construct(
        Twig $twig,
        MenusRepository $menusRepository
    ) {
        $this->twig = $twig;
        $this->menusRepository = $menusRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        if (empty($queryParams['id'])) {
            $menu = $this->menusRepository->findOneBy(['isActive' => true]);
        } else {
            $menu = $this->menusRepository->find($queryParams['id']);
        }

        return $this->twig->render(
            $response,
            'admin/menu.twig',
            [
                'menu' => $menu,
                'showArchived' => isset($queryParams['showArchived'])
            ]
        );
    }
}