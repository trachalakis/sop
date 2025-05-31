<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\User;

interface UsersRepositoryInterface
{
	public function persist(User $user);

	public function delete(User $user);
}