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
		$this->_em->persist($menuItemExtra);
       	$this->_em->flush();
	}

	public function delete(MenuItemExtra $menuItemExtra)
	{
		$this->_em->remove($menuItemExtra);
       	$this->_em->flush();
	}
}