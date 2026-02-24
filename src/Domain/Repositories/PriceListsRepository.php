<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\PriceList;
use Doctrine\ORM\EntityRepository;
use Domain\Repositories\PriceListsRepositoryInterface;

class PriceListsRepository extends EntityRepository implements PriceListsRepositoryInterface
{
	public function persist(PriceList $priceList)
	{
		$this->getEntityManager()->persist($priceList);
	   	$this->getEntityManager()->flush();
	}

	public function delete(PriceList $priceList)
	{
		$this->getEntityManager()->remove($priceList);
	   	$this->getEntityManager()->flush();
	}
}