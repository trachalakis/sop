<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Station;

interface StationsRepositoryInterface
{
	public function persist(Station $station);

	public function delete(Station $station);
}