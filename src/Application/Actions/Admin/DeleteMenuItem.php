<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenuItemsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteMenuItem
{
    private MenuItemsRepositoryInterface $menuItemsRepository;

    public function __construct(MenuItemsRepositoryInterface $menuItemsRepository)
    {
        $this->menuItemsRepository = $menuItemsRepository;
    }

	public function __invoke(Request $request, Response $response)
	{
		$menuItem = $this->menuItemsRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		$this->menuItemsRepository->delete($menuItem);

        return $response->withHeader('Location', '/admin/menu')->withStatus(302);
	}
}