<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Table;

interface TablesRepositoryInterface
{
	public function persist(Table $table);

	public function delete(Table $table);
}