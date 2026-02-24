<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\UserPermission;
use Domain\Repositories\UserPermissionsRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class UserPermissionsRepository extends EntityRepository implements UserPermissionsRepositoryInterface
{
	public function persist(UserPermission $userPermission)
	{
		$this->getEntityManager()->persist($userPermission);
	   	$this->getEntityManager()->flush();
	}

	public function delete(UserPermission $userPermission)
	{
		$this->getEntityManager()->remove($userPermission);
	   	$this->getEntityManager()->flush();
	}
}