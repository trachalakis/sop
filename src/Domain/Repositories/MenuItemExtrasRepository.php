<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\MenuItemExtra;
use Domain\Repositories\MenuExtrasRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class MenuItemExtrasRepository extends EntityRepository implements MenuItemExtrasRepositoryInterface
{
	public function persist(MenuItemExtra $menuItemExtra)
	{
		$this->getEntityManager()->persist($menuItemExtra);
	   	$this->getEntityManager()->flush();
	}

	public function delete(MenuItemExtra $menuItemExtra)
	{
		$this->getEntityManager()->remove($menuItemExtra);
	   	$this->getEntityManager()->flush();
	}
}