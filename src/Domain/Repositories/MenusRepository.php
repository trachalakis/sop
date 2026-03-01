<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Menu;
use Doctrine\ORM\EntityRepository;

class MenusRepository extends EntityRepository
{
	public function persist(Menu $menu)
	{
		$this->getEntityManager()->persist($menu);
	   	$this->getEntityManager()->flush();
	}

	public function delete(Menu $menu)
	{
		$this->getEntityManager()->remove($menu);
	   	$this->getEntityManager()->flush();
	}
}