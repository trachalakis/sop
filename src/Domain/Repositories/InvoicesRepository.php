<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Invoice;
use Doctrine\ORM\EntityRepository;
use Domain\Repositories\InvoicesRepositoryInterface;

class InvoicesRepository extends EntityRepository implements InvoicesRepositoryInterface
{
	public function persist(Invoice $invoice)
	{
		$this->getEntityManager()->persist($invoice);
	   	$this->getEntityManager()->flush();
	}

	public function delete(Invoice $invoice)
	{
		$this->getEntityManager()->remove($invoice);
	   	$this->getEntityManager()->flush();
	}
}