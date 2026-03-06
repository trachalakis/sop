<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTimeImmutable;
use Domain\Entities\Menu;
use Domain\Enums\MenuType;
use Domain\Repositories\MenusRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateMenu
{
    private MenusRepository $menusRepository;

    private Twig $twig;

    public function __construct(
        MenusRepository $menusRepository,
        Twig $twig
    ) {
        $this->menusRepository = $menusRepository;
        $this->twig = $twig;
    }

	public function __invoke(Request $request, Response $response)
	{
		//$menu = $this->menusRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		if ($request->getMethod() == 'POST') {
			$requestData = $request->getParsedBody();

            $menu = new Menu;
            $menu->setIsActive(boolval($requestData['isActive']));
            $menu->setName($requestData['name']);
            $menu->setMenuType(MenuType::from($requestData['menuType']));
            $menu->setCreatedAt(new DateTimeImmutable);

			$this->menusRepository->persist($menu);

            if (function_exists('apcu_clear_cache')) {
            	apcu_clear_cache();
            }

            return $response->withHeader('Location', '/admin/menus')->withStatus(302);
    	}

		return $this->twig->render(
            $response, 
            'admin/create_menu.twig', 
            [
                'menuTypes' => MenuType::cases()
            ]
        );
	}
}