<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\MenuItem;
use Domain\Entities\OrderEntry;
use Doctrine\ORM\EntityRepository;

class OrderEntriesRepository extends EntityRepository
{
	public function persist(OrderEntry $orderEntry)
	{
		$this->getEntityManager()->persist($orderEntry);
	   	$this->getEntityManager()->flush();
	}

	public function delete(OrderEntry $orderEntry)
	{
		$this->getEntityManager()->remove($orderEntry);
	   	$this->getEntityManager()->flush();
	}

	public function findByMenuItemAndPeriod(MenuItem $menuItem, \DatePeriod $datePeriod)
	{
		$qb = $this->getEntityManager()->createQueryBuilder();

		$qb->select('oe')
			->from('Domain\Entities\OrderEntry', 'oe')
			->join('oe.orderEntryGroup', 'oeg')
			->andWhere(
				$qb->expr()->andX(
					$qb->expr()->eq('oe.menuItem', ':menuItem'),
					$qb->expr()->gte('oeg.createdAt', ':startDate'),
					$qb->expr()->lte('oeg.createdAt', ':endDate')
				)
			)
			//->andWhere(
			//	$qb->expr()->andX(
			//		$qb->expr()->gte('oeg.createdAt', ':startDate'),
			//		$qb->expr()->lte('oeg.createdAt', ':endDate')
			//	)
			//)
			->setParameter(':menuItem', $menuItem)
			->setParameter(':startDate', $datePeriod->getStartDate())
			->setParameter(':endDate', $datePeriod->getEndDate());

        return $qb->getQuery()->getResult();

        //return $query->getResult();
	}
}