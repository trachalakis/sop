<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Doctrine\ORM\EntityRepository;
use Domain\Entities\Recipe;

class RecipesRepository extends EntityRepository
{
    public function findMenuItemRecipes(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.menuItem IS NOT NULL')
            ->leftJoin('r.ingredients', 'i')
            ->addSelect('i')
            ->getQuery()
            ->getResult();
    }

    public function persist(Recipe $recipe): void
    {
        $this->getEntityManager()->persist($recipe);
        $this->getEntityManager()->flush();
    }

    public function delete(Recipe $recipe): void
    {
        $this->getEntityManager()->remove($recipe);
        $this->getEntityManager()->flush();
    }
}
