<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Recipe;
use Doctrine\ORM\EntityRepository;

class RecipesRepository extends EntityRepository
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