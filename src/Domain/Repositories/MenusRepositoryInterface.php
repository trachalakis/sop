<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Menu;

interface MenusRepositoryInterface
{
	public function persist(Menu $priceList);

	public function delete(Menu $priceList);
}