<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Supply;
use Domain\Repositories\SuppliesRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class SuppliesRepository extends EntityRepository implements SuppliesRepositoryInterface
{
	public function persist(Supply $supply)
	{
		$this->_em->persist($supply);
       	$this->_em->flush();
	}

	public function delete(Supply $supply)
	{
		$this->_em->remove($supply);
       	$this->_em->flush();
	}
}