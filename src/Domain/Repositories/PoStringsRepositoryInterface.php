<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\PoString;

interface PoStringsRepositoryInterface
{
	public function persist(PoString $poString);

	public function delete(PoString $poString);
}