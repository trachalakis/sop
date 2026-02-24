<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\MenuItemPrice;
use Doctrine\ORM\EntityRepository;
use Domain\Repositories\MenuItemPricesRepositoryInterface;

class MenuItemPricesRepository extends EntityRepository implements MenuItemPricesRepositoryInterface
{
	public function persist(MenuItemPrice $menuItemPrice)
	{
		$this->getEntityManager()->persist($menuItemPrice);
	   	$this->getEntityManager()->flush();
	}

	public function delete(MenuItemPrice $menuItemPrice)
	{
		$this->getEntityManager()->remove($menuItemPrice);
	   	$this->getEntityManager()->flush();
	}
}