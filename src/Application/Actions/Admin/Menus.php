<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenusRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Menus
{
	private MenusRepositoryInterface $menusRepository;

	private Twig $twig;

    public function __construct(
        MenusRepositoryInterface $menusRepository,
        Twig $twig
    ) {
        $this->twig = $twig;
        $this->menusRepository = $menusRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $menus = $this->menusRepository->findBy([], ['createdAt' => 'desc']);

        return $this->twig->render($response, 'admin/menus.twig', ['menus' => $menus]);
    }
}