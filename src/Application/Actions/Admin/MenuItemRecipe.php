<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Recipe;
use Domain\Repositories\MenuItemsRepositoryInterface;
use Domain\Repositories\RecipesRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class MenuItemRecipe
{
	private Twig $twig;

	private RecipesRepositoryInterface $recipesRepository;

	private MenuItemsRepositoryInterface $menuItemsRepository;

	public function __construct(
		Twig $twig,
		MenuItemsRepositoryInterface $menuItemsRepository,
		RecipesRepositoryInterface $recipesRepository
	) {
		$this->twig = $twig;
		$this->menuItemsRepository = $menuItemsRepository;
		$this->recipesRepository = $recipesRepository;
	}

	public function __invoke(Request $request, Response $response)
	{
		$menuItem = $this->menuItemsRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		$recipe = $this->recipesRepository->findOneBy(['menuItem' => $menuItem]);
		if ($recipe == null) {
			$recipe = new Recipe;
			$recipe->setName(null);
			$recipe->setMenuItem($menuItem);
			$recipe->setOutput(null);
			$recipe->setDuration(null);

			$this->recipesRepository->persist($recipe);
		}

        return $response->withHeader('Location', '/admin/recipes/update?id=' . $recipe->getId())->withStatus(302);
	}
}