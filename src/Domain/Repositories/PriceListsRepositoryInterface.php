<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\PriceList;

interface PriceListsRepositoryInterface
{
	public function persist(PriceList $priceList);

	public function delete(PriceList $priceList);
}