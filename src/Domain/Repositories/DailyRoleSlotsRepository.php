<?php

declare(strict_types=1);

namespace Domain\Repositories;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Domain\Entities\DailyRoleSlot;

class DailyRoleSlotsRepository extends EntityRepository
{
    public function findByDate(DateTime $date): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.date = :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function persist(DailyRoleSlot $slot): void
    {
        $this->getEntityManager()->persist($slot);
        $this->getEntityManager()->flush();
    }

    public function delete(DailyRoleSlot $slot): void
    {
        $this->getEntityManager()->remove($slot);
        $this->getEntityManager()->flush();
    }
}
