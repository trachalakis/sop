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
		$this->_em->persist($recipe);
       	$this->_em->flush();
	}

	public function delete(Recipe $recipe)
	{
		$this->_em->remove($recipe);
       	$this->_em->flush();
	}
}