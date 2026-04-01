<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenuItemsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Slim\Views\Twig;

final class DeleteMenuItem
{
    public function __construct(
        private MenuItemsRepository $menuItemsRepository,
        private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $menuItem = $this->menuItemsRepository->find($request->getQueryParams()['id']);

        $menuId = $menuItem->getMenuSection()->getMenu()->getId();

        try {
            $this->menuItemsRepository->delete($menuItem);
        } catch (ForeignKeyConstraintViolationException $e) {
            return $this->twig->render(
                $response,
                'admin/update_menu_item.twig',
                [
                    'menuItem' => $menuItem,
                    'exception' => $e ?? null
                ]
            );
        }

        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        return $response->withHeader('Location', '/admin/menu?id=' . $menuId)->withStatus(302);
    }
}
