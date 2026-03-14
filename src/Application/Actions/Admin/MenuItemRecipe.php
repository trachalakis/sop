<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Domain\Entities\MenuItemIngredient;
use Domain\Repositories\MenuItemIngredientsRepository;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\SuppliesRepository;
use Domain\Repositories\SupplyGroupsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class MenuItemRecipe
{
	private MenuItemIngredientsRepository $menuItemIngredientssRepository;

    private MenuItemsRepository $menuItemsRepository;

    private SuppliesRepository $suppliesRepository;

    private SupplyGroupsRepository $supplyGroupsRepository;

    private Twig $twig;

	public function __construct(
		Twig $twig,
		MenuItemsRepository $menuItemsRepository,
        //MenuItemIngredientsRepository $menuItemIngredientssRepository,
        SuppliesRepository $suppliesRepository,
        SupplyGroupsRepository $supplyGroupsRepository
	) {
		$this->twig = $twig;
        //$this->menuItemIngredientssRepository = $menuItemIngredientssRepository;
        $this->menuItemsRepository = $menuItemsRepository;
        $this->suppliesRepository = $suppliesRepository;
        $this->supplyGroupsRepository = $supplyGroupsRepository;
	}

	public function __invoke(Request $request, Response $response)
	{
		$menuItem = $this->menuItemsRepository->find($request->getQueryParams()['id']);

		if ($request->getMethod() == 'POST') {
    		$requestData = $request->getParsedBody();
        
            //
            $ingredients = new ArrayCollection;
            if (isset($requestData['ingredients'])) {
                foreach ($requestData['ingredients'] as $i) {//dd($requestData);
                    $supply = $this->suppliesRepository->find($i['supply']);
                        
                    $ingredient = new MenuItemIngredient;
                    $ingredient->setQuantity(floatval($i['quantity']));
                    $ingredient->setSupply($supply);
                    $ingredient->setMenuItem($menuItem);
                    
                    $ingredients->add($ingredient);
                }
            }
            $menuItem->setIngredients($ingredients);

            $this->menuItemsRepository->persist($menuItem);
        }

        $supplies = $this->suppliesRepository->findAll();
        $supplyGroups = $this->supplyGroupsRepository->findAll();
        return $this->twig->render(
            $response,
            'admin/menu_item_recipe.twig',
            [
            	'menuItem' => $menuItem,
                'supplies' => $supplies,
                'supplyGroups' => $supplyGroups
            ]
        );
	}
}