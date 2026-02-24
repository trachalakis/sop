<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\PoString;
use Domain\Repositories\OrdersRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class PoStringsRepository extends EntityRepository implements PoStringsRepositoryInterface
{
	public function persist(PoString $poString)
	{
		$this->getEntityManager()->persist($poString);
	   	$this->getEntityManager()->flush();
	}

	public function delete(PoString $poString)
	{
		$this->getEntityManager()->remove($poString);
	   	$this->getEntityManager()->flush();
	}
}