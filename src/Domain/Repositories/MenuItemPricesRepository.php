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
		$this->_em->persist($menuItemPrice);
       	$this->_em->flush();
	}

	public function delete(MenuItemPrice $menuItemPrice)
	{
		$this->_em->remove($menuItemPrice);
       	$this->_em->flush();
	}
}