<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenuSectionsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class MenuSections
{
    private Twig $twig;

    private MenuSectionsRepositoryInterface $menuSectionsRepository;

    public function __construct(Twig $twig, MenuSectionsRepositoryInterface $menuSectionsRepository)
    {
        $this->twig = $twig;
        $this->menuSectionsRepository = $menuSectionsRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $menuSections = $this->menuSectionsRepository->findBy([], ['position' => 'asc']);

        return $this->twig->render($response, 'admin/menu_sections.twig', ['menuSections' => $menuSections]);
    }
}