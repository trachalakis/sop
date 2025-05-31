<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\MenuItemPrice;

interface MenuItemPricesRepositoryInterface
{
	public function persist(MenuItemPrice $menuItemPrice);

	public function delete(MenuItemPrice $menuItemPrice);
}