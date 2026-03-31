<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Doctrine\ORM\EntityRepository;
use Domain\Entities\SupplyPriceHistory;

class SupplyPriceHistoryRepository extends EntityRepository
{
    public function record(SupplyPriceHistory $history): void
    {
        $this->getEntityManager()->persist($history);
        $this->getEntityManager()->flush();
    }
}
