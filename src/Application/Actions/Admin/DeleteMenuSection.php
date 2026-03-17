<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenuSectionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteMenuSection
{
    private MenuSectionsRepository $menuSectionsRepository;

    public function __construct(MenuSectionsRepository $menuSectionsRepository)
    {
        $this->menuSectionsRepository = $menuSectionsRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $menuSection = $this->menuSectionsRepository->find($request->getQueryParams()['id']);

        $menuId = $menuSection->getMenu()->getId();

        $this->menuSectionsRepository->delete($menuSection);

        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        return $response->withHeader('Location', '/admin/menu?id=' . $menuId)->withStatus(302);
    }
}
