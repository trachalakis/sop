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
		$this->_em->persist($invoice);
       	$this->_em->flush();
	}

	public function delete(Invoice $invoice)
	{
		$this->_em->remove($invoice);
       	$this->_em->flush();
	}
}