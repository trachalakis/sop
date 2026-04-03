<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Order;
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Types;

class OrdersRepository extends EntityRepository
{
	public function findByDate(\Datetime $date)
	{
		$start = new \Datetime($date->format('Y-m-d') . ' 5:00:00 AM');
		$end = (clone $start)->add(new \DateInterval('PT23H'));

		$qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('o')
            ->from('Domain\Entities\Order', 'o')
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->gte('o.createdAt', ':start'),
                    $qb->expr()->lt('o.createdAt', ':end')
                )
            )
            ->setParameter(':start', $start, Types::DATE_MUTABLE)
            ->setParameter(':end', $end, Types::DATE_MUTABLE);

        return $qb->getQuery()->getResult();
	}

	public function getNextTicketNumber(\DateTimeImmutable $now): int
	{
		// Day runs from 05:00 to 04:59 next day — same boundary used elsewhere
		$hour = (int) $now->format('H');
		$dayStart = $hour < 5
			? new \DateTimeImmutable($now->format('Y-m-d') . ' 05:00:00')
			: new \DateTimeImmutable($now->format('Y-m-d') . ' 05:00:00');

		if ($hour < 5) {
			$dayStart = (new \DateTimeImmutable($now->format('Y-m-d') . ' 05:00:00'))->modify('-1 day');
		}

		$max = $this->getEntityManager()->createQueryBuilder()
			->select('MAX(o.ticketNumber)')
			->from('Domain\Entities\Order', 'o')
			->where('o.createdAt >= :dayStart')
			->andWhere('o.table IS NULL')
			->setParameter('dayStart', $dayStart)
			->getQuery()
			->getSingleScalarResult();

		return ($max === null ? 0 : (int) $max) + 1;
	}

	public function findActiveTableOrders(): array
	{
		return $this->getEntityManager()->createQueryBuilder()
			->select('o')
			->from('Domain\Entities\Order', 'o')
			->where('o.status = :status')
			->andWhere('o.table IS NOT NULL')
			->setParameter('status', 'OPEN')
			->orderBy('o.createdAt', 'desc')
			->getQuery()
			->getResult();
	}

	public function findActiveTakeOutOrders(): array
	{
		return $this->getEntityManager()->createQueryBuilder()
			->select('o')
			->from('Domain\Entities\Order', 'o')
			->where('o.status = :status')
			->andWhere('o.table IS NULL')
			->setParameter('status', 'OPEN')
			->orderBy('o.createdAt', 'desc')
			->getQuery()
			->getResult();
	}

	public function persist(Order $order)
	{
		$this->getEntityManager()->persist($order);
	   	$this->getEntityManager()->flush();
	}

	public function delete(Order $order)
	{
		$this->getEntityManager()->remove($order);
	   	$this->getEntityManager()->flush();
	}
}