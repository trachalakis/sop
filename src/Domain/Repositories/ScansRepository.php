<?php

declare(strict_types=1);

namespace Domain\Repositories;

use DatePeriod;
use Domain\Entities\Scan;
use Domain\Entities\User;
use Domain\Repositories\UsersRepositoryInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Types;

class ScansRepository extends EntityRepository implements ScansRepositoryInterface
{
	public function add(Scan $scan)
	{
        $this->getEntityManager()->persist($scan);
        $this->getEntityManager()->flush();
	}

	public function persist(Scan $scan)
	{
        $this->getEntityManager()->persist($scan);
        $this->getEntityManager()->flush();
	}

    public function delete(Scan $scan)
    {
        $this->getEntityManager()->remove($scan);
        $this->getEntityManager()->flush();
    }

	public function findLastUserCheckIn($user)
	{
		$qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('s')
            ->from(Scan::class, 's')
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('s.checkIn'),
                    $qb->expr()->isNull('s.checkOut'),
                    $qb->expr()->eq('s.user', ':user')
                )
            )
            ->setParameter(':user', $user);

        $result = $qb->getQuery()->getResult();

       	if (count($result) == 0) {
       		return null;
       	} else if (count($result) == 1) {
       		return $qb->getQuery()->getResult()[0];
       	} else {
       		throw new \Exception('Scan table is corrupt');
       	}
	}

    public function findCheckInsByDate(\Datetime $datetime)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('s')
            ->from(Scan::class, 's')
            ->andWhere(
                $qb->expr()->eq('DATE(s.checkIn)', ':checkIn')
            )
            ->setParameter(':checkIn', $datetime->format('Y-m-d'));

        return $qb->getQuery()->getResult();
    }

    public function findUserScans(User $user, DatePeriod $datePeriod)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('s')
            ->from(Scan::class, 's')
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('s.user', ':user'),
                    $qb->expr()->gte('s.checkIn', ':startDate'),
                    $qb->expr()->lt('s.checkIn', ':endDate')
                )
            )
            ->setParameter(':user', $user)
            ->setParameter(':startDate', $datePeriod->getStartDate())
            ->setParameter(':endDate', $datePeriod->getEndDate())
            ->orderBy('s.checkIn', 'asc');

        return $qb->getQuery()->getResult();
    }
}