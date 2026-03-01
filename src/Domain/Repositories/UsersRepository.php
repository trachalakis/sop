<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\User;
use Doctrine\ORM\EntityRepository;

class UsersRepository extends EntityRepository
{
	public function persist(User $user)
	{
		$this->getEntityManager()->persist($user);
       	$this->getEntityManager()->flush();
	}

	public function delete(User $user)
	{
		$this->getEntityManager()->remove($user);
       	$this->getEntityManager()->flush();
	}
}