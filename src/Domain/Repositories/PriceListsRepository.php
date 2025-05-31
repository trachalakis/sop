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
		$this->_em->persist($priceList);
       	$this->_em->flush();
	}

	public function delete(PriceList $priceList)
	{
		$this->_em->remove($priceList);
       	$this->_em->flush();
	}
}