<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Recipe;
use Domain\Repositories\RecipesRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class RecipesRepository extends EntityRepository implements RecipesRepositoryInterface
{
	public function persist(Recipe $recipe)
	{
        $this->getEntityManager()->persist($recipe);
       	$this->getEntityManager()->flush();
	}

	public function delete(Recipe $recipe)
	{
        $this->getEntityManager()->remove($recipe);
       	$this->getEntityManager()->flush();
	}
}