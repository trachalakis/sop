<?php

declare(strict_types=1);

namespace Domain\Repositories;

use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;
use Domain\Entities\ShoppingList;

class ShoppingListsRepository extends EntityRepository
{
    public function persist(ShoppingList $shoppingList): void
    {
        $this->getEntityManager()->persist($shoppingList);
        $this->getEntityManager()->flush();
    }

    public function findByDate(DateTimeImmutable $date): ?ShoppingList
    {
        return $this->findOneBy(['date' => new DateTimeImmutable($date->format('Y-m-d'))]);
    }
}
