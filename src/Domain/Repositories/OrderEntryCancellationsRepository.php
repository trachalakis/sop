<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\OrderEntryCancellation;
use Domain\Repositories\OrderEntryCancellationsRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class OrderEntryCancellationsRepository extends EntityRepository implements OrderEntryCancellationsRepositoryInterface
{
	public function persist(OrderEntryCancellation $orderEntryCancellation)
	{
		$this->_em->persist($orderEntryCancellation);
       	$this->_em->flush();
	}

	public function delete(OrderEntryCancellation $orderEntryCancellation)
	{
		$this->_em->remove($orderEntryCancellation);
       	$this->_em->flush();
	}
}