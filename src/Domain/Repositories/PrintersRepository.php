<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Printer;
use Doctrine\ORM\EntityRepository;

class PrintersRepository extends EntityRepository
{
	public function persist(Printer $printer)
	{
        $this->getEntityManager()->persist($printer);
       	$this->getEntityManager()->flush();
	}

	public function delete(Printer $printer)
	{
        $this->getEntityManager()->remove($printer);
       	$this->getEntityManager()->flush();
	}
}