<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenusRepository;
use Domain\Repositories\MenuSectionsRepository;
use Domain\Services\CloneMenuSection;
use Domain\Services\CloneMenuItem;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CopyMenuSection
{
    private MenuSectionsRepository $menuSectionsRepository;

    private MenusRepository $menusRepository;

    public function __construct(
        MenuSectionsRepository $menuSectionsRepository,
        MenusRepository $menusRepository,
    ) {
        $this->menuSectionsRepository = $menuSectionsRepository;
        $this->menusRepository = $menusRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $menuSection = $this->menuSectionsRepository->find($request->getQueryParams()['id']);
        $targetMenu = $this->menusRepository->find($request->getQueryParams()['target']);
        $clone = (new CloneMenuSection(new CloneMenuItem))($menuSection, $targetMenu);

        $this->menuSectionsRepository->persist($clone);

        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        return $response->withHeader('Location', '/admin/menu-sections/update?id=' . $clone->getId())->withStatus(302);
    }
}