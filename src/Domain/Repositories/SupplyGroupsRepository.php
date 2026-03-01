<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\SupplyGroup;
use Doctrine\ORM\EntityRepository;

class SupplyGroupsRepository extends EntityRepository
{
	public function persist(SupplyGroup $supplyGroup)
	{
        $this->getEntityManager()->persist($supplyGroup);
       	$this->getEntityManager()->flush();
	}

	public function delete(SupplyGroup $supplyGroup)
	{
        $this->getEntityManager()->remove($supplyGroup);
       	$this->getEntityManager()->flush();
	}
}