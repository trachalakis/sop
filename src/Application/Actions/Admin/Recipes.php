<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\RecipesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Recipes
{
    private Twig $twig;

    private RecipesRepository $recipesRepository;

    public function __construct(
        Twig $twig,
        RecipesRepository $recipesRepository
    ) {
        $this->twig = $twig;
        $this->recipesRepository = $recipesRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $recipes = $this->recipesRepository->findBy([], ['name' => 'asc']);

        return $this->twig->render($response, 'admin/recipes.twig', ['recipes' => $recipes]);
    }
}