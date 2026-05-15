<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\EcrJob;
use Doctrine\ORM\EntityRepository;

class EcrJobsRepository extends EntityRepository
{
    public function persist(EcrJob $job): void
    {
        $this->getEntityManager()->persist($job);
        $this->getEntityManager()->flush();
    }

    /** @return EcrJob[] */
    public function findPending(): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.status = :status')
            ->andWhere('j.attempts < :maxAttempts')
            ->setParameter('status', 'pending')
            ->setParameter('maxAttempts', 5)
            ->getQuery()
            ->getResult();
    }
}
