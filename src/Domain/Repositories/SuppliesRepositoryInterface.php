<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Supply;

interface SuppliesRepositoryInterface
{
	public function persist(Supply $supply);

	public function delete(Supply $supply);
}