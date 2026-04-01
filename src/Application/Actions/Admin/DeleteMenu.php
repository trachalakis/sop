<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenusRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Slim\Views\Twig;

final class DeleteMenu
{
    public function __construct(
        private MenusRepository $menusRepository,
        private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $menu = $this->menusRepository->find($request->getQueryParams()['id']);

        try {
            $this->menusRepository->delete($menu);
        } catch (ForeignKeyConstraintViolationException $e) {
            return $this->twig->render(
                $response,
                'admin/update_menu.twig',
                [
                    'menu' => $menu,
                    'exception' => $e ?? null
                ]
            );
        }

        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        return $response->withHeader('Location', '/admin/menus')->withStatus(302);
    }
}
