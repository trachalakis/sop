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
		$this->_em->persist($table);
       	$this->_em->flush();
	}

	public function delete(Table $table)
	{
		$this->_em->remove($table);
       	$this->_em->flush();
	}
}