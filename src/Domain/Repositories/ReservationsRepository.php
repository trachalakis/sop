<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Reservation;
use Domain\Repositories\ReservatonsRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class ReservationsRepository extends EntityRepository implements ReservationsRepositoryInterface
{
	public function findByDate(\Datetime $date)
	{
		$qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('r')
            ->from('Domain\Entities\Reservation', 'r')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('r.dateTime', ':date')
                )
            )
            ->orderBy('r.dateTime', 'asc')
            ->setParameter(':date', '%' . $date->format('Y-m-d') . '%');

        return $qb->getQuery()->getResult();
	}

	public function persist(Reservation $reservation)
	{
		$this->_em->persist($reservation);
       	$this->_em->flush();
	}

	public function delete(Reservation $reservation)
	{
		$this->_em->remove($reservation);
       	$this->_em->flush();
	}
}