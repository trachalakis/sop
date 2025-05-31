<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\OrderEntryGroup;

interface OrderEntryGroupsRepositoryInterface
{
	public function persist(OrderEntryGroup $orderEntryGroup);

	public function delete(OrderEntryGroup $orderEntryGroup);
}