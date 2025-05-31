<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Station;
use Domain\Repositories\StationsRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class StationsRepository extends EntityRepository implements StationsRepositoryInterface
{
	public function persist(Station $station)
	{
		$this->_em->persist($station);
       	$this->_em->flush();
	}

	public function delete(Station $station)
	{
		$this->_em->remove($station);
       	$this->_em->flush();
	}
}