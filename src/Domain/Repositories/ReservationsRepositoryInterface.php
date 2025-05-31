<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Reservation;

interface ReservationsRepositoryInterface
{
	public function persist(Reservation $reservation);

	public function delete(Reservation $reservation);
}