<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\OrderEntry;

interface OrderEntriesRepositoryInterface
{
	public function persist(OrderEntry $orderEntry);

	public function delete(OrderEntry $orderEntry);
}