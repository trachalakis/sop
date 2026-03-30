<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\RecipesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteRecipe
{
    public function __construct(
        private RecipesRepository $recipesRepository
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $recipe = $this->recipesRepository->find($request->getQueryParams()['id']);
        $this->recipesRepository->delete($recipe);

        return $response->withHeader('Location', '/admin/recipes')->withStatus(302);
    }
}
