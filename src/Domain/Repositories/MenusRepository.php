<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Menu;
use Doctrine\ORM\EntityRepository;
use Domain\Repositories\MenusRepositoryInterface;

class MenusRepository extends EntityRepository implements MenusRepositoryInterface
{
	public function persist(Menu $menu)
	{
		$this->_em->persist($menu);
       	$this->_em->flush();
	}

	public function delete(Menu $menu)
	{
		$this->_em->remove($menu);
       	$this->_em->flush();
	}
}