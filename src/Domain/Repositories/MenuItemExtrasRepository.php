<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\MenuItemExtra;
use Doctrine\ORM\EntityRepository;

class MenuItemExtrasRepository extends EntityRepository
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