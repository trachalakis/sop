<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenuItemsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ToggleMenuItem
{
    private MenuItemsRepository $menuItemsRepository;

    public function __construct(
        MenuItemsRepository $menuItemsRepository
    ) {
        $this->menuItemsRepository = $menuItemsRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $menuItem = $this->menuItemsRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

        $menuItem->setIsActive(!$menuItem->getIsActive());

        $this->menuItemsRepository->persist($menuItem);

        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        //return $response->withHeader('Location', '/admin/menu')->withStatus(302);
        $response->getBody()->write('ok');
        return $response;
    }
}