<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Supply;
use Doctrine\ORM\EntityRepository;

class SuppliesRepository extends EntityRepository
{
	public function persist(Supply $supply)
	{
        $this->getEntityManager()->persist($supply);
       	$this->getEntityManager()->flush();
	}

	public function delete(Supply $supply)
	{
        $this->getEntityManager()->remove($supply);
       	$this->getEntityManager()->flush();
	}
}