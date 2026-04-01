<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenuSectionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Slim\Views\Twig;

final class DeleteMenuSection
{
    public function __construct(
        private MenuSectionsRepository $menuSectionsRepository,
        private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $menuSection = $this->menuSectionsRepository->find($request->getQueryParams()['id']);

        $menuId = $menuSection->getMenu()->getId();

        try {
            $this->menuSectionsRepository->delete($menuSection);
        } catch (ForeignKeyConstraintViolationException $e) {
            return $this->twig->render(
                $response,
                'admin/update_menu_section.twig',
                [
                    'menuSection' => $menuSection,
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
