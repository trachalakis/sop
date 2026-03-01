<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Table;
use Doctrine\ORM\EntityRepository;

class TablesRepository extends EntityRepository
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