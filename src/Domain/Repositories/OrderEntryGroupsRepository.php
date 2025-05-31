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
		$this->_em->persist($orderEntryGroup);
       	$this->_em->flush();
	}

	public function delete(OrderEntryGroup $orderEntryGroup)
	{
		$this->_em->remove($orderEntryGroup);
       	$this->_em->flush();
	}
}