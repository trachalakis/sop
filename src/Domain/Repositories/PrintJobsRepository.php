<?php

declare(strict_types=1);

namespace Domain\Repositories;

use DateTimeImmutable;
use Domain\Entities\PrintJob;
use Doctrine\ORM\EntityRepository;

class PrintJobsRepository extends EntityRepository
{
	public function persist(PrintJob $printJob)
	{
        $this->getEntityManager()->persist($printJob);
       	$this->getEntityManager()->flush();
	}

	public function delete(PrintJob $printJob)
	{
        $this->getEntityManager()->remove($printJob);
       	$this->getEntityManager()->flush();
	}

	public function findSince(DateTimeImmutable $since): array
	{
		return $this->createQueryBuilder('p')
			->where('p.createdAt >= :since')
			->setParameter('since', $since)
			->orderBy('p.createdAt', 'desc')
			->getQuery()
			->getResult();
	}
}