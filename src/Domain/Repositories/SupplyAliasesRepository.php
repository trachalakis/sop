<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Doctrine\ORM\EntityRepository;
use Domain\Entities\SupplyAlias;

class SupplyAliasesRepository extends EntityRepository
{
    public function persist(SupplyAlias $alias): void
    {
        $this->getEntityManager()->persist($alias);
        $this->getEntityManager()->flush();
    }

    public function delete(SupplyAlias $alias): void
    {
        $this->getEntityManager()->remove($alias);
        $this->getEntityManager()->flush();
    }

    /**
     * Find a specific alias by supplier + description, or null if not yet mapped.
     */
    public function findBySupplierAndDescription(int $supplierId, string $description): ?SupplyAlias
    {
        return $this->findOneBy([
            'supplier' => $supplierId,
            'description' => $description,
        ]);
    }
}
