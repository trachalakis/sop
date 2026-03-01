<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Ingredient;
use Domain\Entities\Recipe;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\RecipesRepository;
use Domain\Repositories\SuppliesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateRecipe
{
    private MenuItemsRepository $menuItemsRepository;

    private RecipesRepository $recipesRepository;

    private SuppliesRepository $suppliesRepository;

    private Twig $twig;

    public function __construct(
    	MenuItemsRepository $menuItemsRepository,
    	RecipesRepository $recipesRepository,
    	SuppliesRepository $suppliesRepository,
    	Twig $twig
    ) {
        $this->menuItemsRepository = $menuItemsRepository;
        $this->recipesRepository = $recipesRepository;
        $this->suppliesRepository = $suppliesRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
	{
		if ($request->getMethod() == 'POST') {
            $requestData = $request->getParsedBody();

            $recipe = new Recipe;
            $recipe->setName($requestData['name']);
            $recipe->setDuration(intval($requestData['duration']));
            $recipe->setComments($requestData['comments']);
            $recipe->setYield(floatval($requestData['yield']));
            $recipe->setYieldUnit($requestData['yieldUnit']);

            $menuItem = $this->menuItemsRepository->findOneBy(['id' => $requestData['menuItem']]);
            $recipe->setMenuItem($menuItem);

            $ingredients = [];
            if (isset($requestData['supply'])) {
	            foreach($requestData['supply'] as $s) {
	            	$supply = $this->suppliesRepository->findOneBy(['id' => $s['id']]);

	            	$ingredient = new Ingredient;
	            	$ingredient->setSupply($supply);
	            	$ingredient->setQuantity(floatval($s['quantity']));
	            	$ingredient->setUnit($s['unit']);
	            	$ingredient->setRecipe($recipe);

	            	$ingredients[] = $ingredient;
	            }
	        }

	        if (isset($requestData['preparation'])) {
	            foreach($requestData['preparation'] as $p) {
	            	$preparation = $this->recipesRepository->findOneBy(['id' => $p['id']]);

	            	$ingredient = new Ingredient;
	            	$ingredient->setPreparation($preparation);
	            	$ingredient->setQuantity(floatval($p['quantity']));
	            	$ingredient->setUnit($p['unit']);
	            	$ingredient->setRecipe($recipe);

	            	$ingredients[] = $ingredient;
	            }
	        }
            $recipe->setIngredients($ingredients);

            $this->recipesRepository->persist($recipe);

            return $response->withHeader('Location', '/admin/recipes')->withStatus(302);
        }

        $preparations = $this->recipesRepository->findBy(['menuItem' => null], ['name' => 'asc']);
        $supplies = $this->suppliesRepository->findBy([], ['name' => 'asc']);
        $menuItems = $this->menuItemsRepository->findBy(['isArchived' => false], []);
        return $this->twig->render(
        	$response,
        	'admin/create_recipe.twig',
        	[
        		'preparations' => $preparations,
        		'supplies' => $supplies,
        		'menuItems' => $menuItems
        	]
        );
	}
}