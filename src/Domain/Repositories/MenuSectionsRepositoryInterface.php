<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\MenuSection;

interface MenuSectionsRepositoryInterface
{
	public function persist(MenuSection $menuSection);

	public function delete(MenuSection $menuSection);
}