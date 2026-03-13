<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\MenuItemIngredient;
use Doctrine\ORM\EntityRepository;

class MenuItemIngredientsRepository extends EntityRepository
{
	public function persist(MenuItemIngredient $menuItemIngredient)
	{
        $this->getEntityManager()->persist($menuItemIngredient);
       	$this->getEntityManager()->flush();
	}

	public function delete(MenuItemIngredient $menuItemIngredient)
	{
        $this->getEntityManager()->remove($menuItemIngredient);
       	$this->getEntityManager()->flush();
	}
}