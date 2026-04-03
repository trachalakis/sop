<?php

declare(strict_types=1);

namespace Domain\Repositories;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Domain\Entities\WorkShift;

class WorkShiftsRepository extends EntityRepository
{
    public function findByDate(DateTime $date): array
    {
        $from = (clone $date)->setTime(0, 0, 0);
        $to   = (clone $date)->setTime(23, 59, 59);

        return $this->createQueryBuilder('ws')
            ->where('ws.start >= :from')
            ->andWhere('ws.start <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('ws.start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function persist(WorkShift $shift): void
    {
        $this->getEntityManager()->persist($shift);
        $this->getEntityManager()->flush();
    }

    public function delete(WorkShift $shift): void
    {
        $this->getEntityManager()->remove($shift);
        $this->getEntityManager()->flush();
    }
}
