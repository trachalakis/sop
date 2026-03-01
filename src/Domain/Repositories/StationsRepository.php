<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Station;
use Doctrine\ORM\EntityRepository;

class StationsRepository extends EntityRepository
{
	public function persist(Station $station)
	{
        $this->getEntityManager()->persist($station);
       	$this->getEntityManager()->flush();
	}

	public function delete(Station $station)
	{
        $this->getEntityManager()->remove($station);
       	$this->getEntityManager()->flush();
	}
}