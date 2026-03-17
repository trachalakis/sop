<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenuItemsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ToggleArchive
{
    private MenuItemsRepository $menuItemsRepository;

    public function __construct(MenuItemsRepository $menuItemsRepository)
    {
        $this->menuItemsRepository = $menuItemsRepository;
    }

	public function __invoke(Request $request, Response $response)
	{
        $menuItem = $this->menuItemsRepository->find($request->getQueryParams()['id']);

        $menuItem->setIsArchived(!$menuItem->getIsArchived());
        if ($menuItem->getIsArchived()) {
            $menuItem->setIsActive(false);
        }
        
		$this->menuItemsRepository->persist($menuItem);

        return $response->withHeader('Location', '/admin/menu?id=' . $menuItem->getMenuSection()->getMenu()->getId())->withStatus(302);
	}
}