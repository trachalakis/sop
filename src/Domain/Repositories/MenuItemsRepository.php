<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\MenuItem;
use Domain\Repositories\MenuItemsRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class MenuItemsRepository extends EntityRepository implements MenuItemsRepositoryInterface
{
	public function persist(MenuItem $menuItem)
	{
		$this->_em->persist($menuItem);
       	$this->_em->flush();
	}

	public function delete(MenuItem $menuItem)
	{
		$this->_em->remove($menuItem);
       	$this->_em->flush();
	}
}