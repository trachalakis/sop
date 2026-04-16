<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Doctrine\ORM\EntityRepository;
use Domain\Entities\Supplier;

class SuppliersRepository extends EntityRepository
{
    public function persist(Supplier $supplier): void
    {
        $this->getEntityManager()->persist($supplier);
        $this->getEntityManager()->flush();
    }

    public function delete(Supplier $supplier): void
    {
        $this->getEntityManager()->remove($supplier);
        $this->getEntityManager()->flush();
    }
}
