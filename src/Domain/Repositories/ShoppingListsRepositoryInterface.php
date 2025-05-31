<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\ShoppingList;

interface ShoppingListsRepositoryInterface
{
	public function persist(ShoppingList $shoppingList);

	public function delete(ShoppingList $shoppingList);
}