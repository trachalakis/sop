<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Order;
use Domain\Repositories\OrdersRepositoryInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Types;

class OrdersRepository extends EntityRepository implements OrdersRepositoryInterface
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