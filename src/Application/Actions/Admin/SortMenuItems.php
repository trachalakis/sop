<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenuItemsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SortMenuItems
{
    private MenuItemsRepository $menuItemsRepository;

    public function __construct(
    	MenuItemsRepository $menuItemsRepository
    ) {
        $this->menuItemsRepository = $menuItemsRepository;
    }

    public function __invoke(Request $request, Response $response)
	{
		$menuItemIds = json_decode(file_get_contents('php://input'), true);
		$position = 1;

		foreach($menuItemIds as $menuItemId) {
			$menuItem = $this->menuItemsRepository->findOneBy(['id' => $menuItemId]);
			$menuItem->setPosition($position++);
			$this->menuItemsRepository->persist($menuItem);
		}

		$response->getBody()->write('ok');
		return $response;
	}
}
