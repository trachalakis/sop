<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Order;

interface OrdersRepositoryInterface
{
	public function persist(Order $order);

	public function delete(Order $order);
}