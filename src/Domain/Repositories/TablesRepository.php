<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Table;
use Domain\Repositories\TablesRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class TablesRepository extends EntityRepository implements TablesRepositoryInterface
{
	public function persist(Table $table)
	{
		$this->getEntityManager()->persist($table);
	   	$this->getEntityManager()->flush();
	}

	public function delete(Table $table)
	{
		$this->getEntityManager()->remove($table);
	   	$this->getEntityManager()->flush();
	}
}