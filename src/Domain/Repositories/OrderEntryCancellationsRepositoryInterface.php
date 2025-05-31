<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\OrderEntryCancellation;

interface OrderEntryCancellationsRepositoryInterface
{
	public function persist(OrderEntryCancellation $orderEntryCancellation);

	public function delete(OrderEntryCancellation $orderEntryCancellation);
}