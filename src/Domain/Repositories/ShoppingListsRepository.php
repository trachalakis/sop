<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\ShoppingList;
use Domain\Repositories\ShoppingListsRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class ShoppingListsRepository extends EntityRepository implements ShoppingListsRepositoryInterface
{
	public function persist(ShoppingList $shoppingList)
	{
		$this->_em->persist($shoppingList);
       	$this->_em->flush();
	}

	public function delete(ShoppingList $shoppingList)
	{
		$this->_em->remove($shoppingList);
       	$this->_em->flush();
	}
}