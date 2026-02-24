<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Supplier;
use Domain\Repositories\SuppliersRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class SuppliersRepository extends EntityRepository implements SuppliersRepositoryInterface
{
	public function persist(Supplier $supplier)
	{
		$this->getEntityManager()->persist($supplier);
	   	$this->getEntityManager()->flush();
	}

	public function delete(Supplier $supplier)
	{
		$this->getEntityManager()->remove($supplier);
	   	$this->getEntityManager()->flush();
	}
}