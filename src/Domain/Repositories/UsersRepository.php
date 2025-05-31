<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\User;
use Domain\Repositories\UsersRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class UsersRepository extends EntityRepository implements UsersRepositoryInterface
{
	public function persist(User $user)
	{
		$this->_em->persist($user);
       	$this->_em->flush();
	}

	public function delete(User $user)
	{
		$this->_em->remove($user);
       	$this->_em->flush();
	}
}