<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\MenuItemExtra;

interface MenuItemExtrasRepositoryInterface
{
	public function persist(MenuItemExtra $menuItemExtra);

	public function delete(MenuItemExtra $menuItemExtra);
}