<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\SupplyGroup;

interface SupplyGroupsRepositoryInterface
{
	public function persist(SupplyGroup $supplyGroup);

	public function delete(SupplyGroup $supplyGroup);
}