<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Reservation;
use Doctrine\ORM\EntityRepository;

class ReservationsRepository extends EntityRepository implements ReservationsRepositoryInterface
{
	public function findByDate(\Datetime $date)
	{
		$qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('r')
            ->from(Reservation::class, 'r')
            ->andWhere(
                $qb->expr()->eq('DATE(r.dateTime)', ':date')
            )
            ->orderBy('r.dateTime', 'asc')
            ->setParameter(':date', $date->format('Y-m-d'));

        return $qb->getQuery()->getResult();
	}

	public function persist(Reservation $reservation)
	{
		$this->getEntityManager()->persist($reservation);
	   	$this->getEntityManager()->flush();
	}

	public function delete(Reservation $reservation)
	{
		$this->getEntityManager()->remove($reservation);
	   	$this->getEntityManager()->flush();
	}
}