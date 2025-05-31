<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Recipe;

interface RecipesRepositoryInterface
{
	public function persist(Recipe $recipe);

	public function delete(Recipe $recipe);
}