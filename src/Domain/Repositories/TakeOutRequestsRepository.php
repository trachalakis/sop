<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\TakeOutRequest;
use Domain\Enums\TakeOutRequestStatus;
use Doctrine\ORM\EntityRepository;

class TakeOutRequestsRepository extends EntityRepository
{
    public function findPending(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('r')
            ->from('Domain\Entities\TakeOutRequest', 'r')
            ->where('r.status = :status')
            ->setParameter('status', TakeOutRequestStatus::Pending)
            ->orderBy('r.createdAt', 'asc')
            ->getQuery()
            ->getResult();
    }

    public function findOneByToken(string $token): ?TakeOutRequest
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function countPendingByPhone(string $phone): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from('Domain\Entities\TakeOutRequest', 'r')
            ->where('r.status = :status')
            ->andWhere('r.customerPhone = :phone')
            ->setParameter('status', TakeOutRequestStatus::Pending)
            ->setParameter('phone', $phone)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function persist(TakeOutRequest $request)
    {
        $this->getEntityManager()->persist($request);
        $this->getEntityManager()->flush();
    }
}
