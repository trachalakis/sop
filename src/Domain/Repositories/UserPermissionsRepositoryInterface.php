<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\UserPermission;

interface UserPermissionsRepositoryInterface
{
	public function persist(UserPermission $userPermission);

	public function delete(UserPermission $userPermission);
}