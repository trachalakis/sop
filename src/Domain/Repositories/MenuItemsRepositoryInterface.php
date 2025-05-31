<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\MenuItem;

interface MenuItemsRepositoryInterface
{
	public function persist(MenuItem $menuItem);

	public function delete(MenuItem $menuItem);
}