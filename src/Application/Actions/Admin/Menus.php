<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenusRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Menus
{
    public function __construct(
        private MenusRepository $menusRepository,
        private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $menus = $this->menusRepository->findBy([], ['isActive' => 'desc', 'createdAt' => 'desc']);

        return $this->twig->render($response, 'admin/menus.twig', ['menus' => $menus]);
    }
}