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
		$this->_em->persist($poString);
       	$this->_em->flush();
	}

	public function delete(PoString $poString)
	{
		$this->_em->remove($poString);
       	$this->_em->flush();
	}
}