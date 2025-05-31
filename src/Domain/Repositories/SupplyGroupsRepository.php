<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\SupplyGroup;
use Doctrine\ORM\EntityRepository;
use Domain\Repositories\SupplyGroupsRepositoryInterface;

class SupplyGroupsRepository extends EntityRepository implements SupplyGroupsRepositoryInterface
{
	public function persist(SupplyGroup $supplyGroup)
	{
		$this->_em->persist($supplyGroup);
       	$this->_em->flush();
	}

	public function delete(SupplyGroup $supplyGroup)
	{
		$this->_em->remove($supplyGroup);
       	$this->_em->flush();
	}
}