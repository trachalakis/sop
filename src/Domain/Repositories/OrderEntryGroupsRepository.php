<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\OrderEntryGroup;
use Domain\Repositories\OrderEntryGroupsRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class OrderEntryGroupsRepository extends EntityRepository implements OrderEntryGroupsRepositoryInterface
{
	public function persist(OrderEntryGroup $orderEntryGroup)
	{
        $this->getEntityManager()->persist($orderEntryGroup);
       	$this->getEntityManager()->flush();
	}

	public function delete(OrderEntryGroup $orderEntryGroup)
	{
        $this->getEntityManager()->remove($orderEntryGroup);
       	$this->getEntityManager()->flush();
	}
}