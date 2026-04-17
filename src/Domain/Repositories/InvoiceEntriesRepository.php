<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Doctrine\ORM\EntityRepository;
use Domain\Entities\InvoiceEntry;
use Domain\Entities\SupplyAlias;

class InvoiceEntriesRepository extends EntityRepository
{
    /**
     * Find all entries for a given supplier + description combination and set their supply alias.
     * Used when the user maps an invoice description to a Supply for the first time.
     */
    public function linkAllBySupplierAndDescription(int $supplierId, string $description, SupplyAlias $alias): void
    {
        $entries = $this->getEntityManager()->createQuery(
            'SELECT e FROM Domain\Entities\InvoiceEntry e
             JOIN e.invoice i
             WHERE e.description = :desc AND i.supplier = :supplierId'
        )
        ->setParameter('desc', $description)
        ->setParameter('supplierId', $supplierId)
        ->getResult();

        foreach ($entries as $entry) {
            $entry->setSupplyAlias($alias);
        }
        $this->getEntityManager()->flush();
    }
}
