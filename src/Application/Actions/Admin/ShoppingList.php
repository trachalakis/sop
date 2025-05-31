<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Datetime;
use Domain\Entities\ShoppingList;
use Domain\Entities\ShoppingListItem;
use Domain\Repositories\ShoppingListsRepositoryInterface;
use Domain\Repositories\SuppliesRepositoryInterface;
use Domain\Repositories\SupplyGroupsRepositoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class MakeShoppingList
{
	private ShoppingListsRepositoryInterface $shoppingListsRepository;

    private SuppliesRepositoryInterface $suppliesRepository;

    private SupplyGroupsRepositoryInterface $supplyGroupsRepository;

    private Twig $twig;

    public function __construct(
        ShoppingListsRepositoryInterface $shoppingListsRepository,
        SuppliesRepositoryInterface $suppliesRepository,
        SupplyGroupsRepositoryInterface $supplyGroupsRepository,
        Twig $twig
    ) {
        $this->shoppingListsRepository = $shoppingListsRepository;
        $this->suppliesRepository = $suppliesRepository;
        $this->supplyGroupsRepository = $supplyGroupsRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
	{
		if ((new Datetime())->format('G') >= 6) {
            $date = new Datetime('tomorrow');
        } else {
            $date = new Datetime;
        }

        $shoppingList = $this->shoppingListsRepository->findOneBy(['date' => $date]);
        if ($ShoppingList === null) {
        	$shoppingList = new ShoppingList;
        	$shoppingList->setDate($date);
        	$this->shoppingListsRepository->persist($shoppingList);
        }

		if ($request->getMethod() == 'POST') {
            $requestData = $request->getParsedBody();

            //$shoppingList = new ShoppingList;
            //if ((new Datetime())->format('G') >= 6) {
            //    $date = new Datetime('tomorrow');
            //} else {
            //    $date = new Datetime();
            //}
            //$shoppingList->setDate($date);

            $shoppingListItems = new ArrayCollection;
            foreach($requestData['quantity'] as $id => $quantity) {
                if ($quantity > 0) {
                    $supply = $this->suppliesRepository->findOneBy(['id' => $id]);

                    $shoppingListItem = new ShoppingListItem;
                    $shoppingListItem->setSupply($supply);
                    $shoppingListItem->setShoppingList($shoppingList);
                    $shoppingListItem->setQuantity(floatval($quantity));

                    $shoppingListItems->add($shoppingListItem);
                }
            }
            $shoppingList->setShoppingListItems($shoppingListItems);

            $this->shoppingListsRepository->persist($shoppingList);

            return $response->withHeader('Location', '/admin/shopping-list')->withStatus(302);
        }

        $supplyGroups = $this->supplyGroupsRepository->findBy([], ['name' => 'asc']);
        return $this->twig->render(
            $response,
            'admin/shopping_list.twig',
            [
                'supplyGroups' => $supplyGroups
            ]
        );
	}
}