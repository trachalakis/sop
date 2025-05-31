<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Supplier;

interface SuppliersRepositoryInterface
{
	public function persist(Supplier $supply);

	public function delete(Supplier $supply);
}