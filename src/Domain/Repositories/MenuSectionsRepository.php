<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\MenuSection;
use Domain\Repositories\MenuSectionsRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class MenuSectionsRepository extends EntityRepository implements MenuSectionsRepositoryInterface
{
	public function persist(MenuSection $menuSection)
	{
		$this->getEntityManager()->persist($menuSection);
	   	$this->getEntityManager()->flush();
	}

	public function delete(MenuSection $menuSection)
	{
		$this->getEntityManager()->remove($menuSection);
	   	$this->getEntityManager()->flush();
	}
}