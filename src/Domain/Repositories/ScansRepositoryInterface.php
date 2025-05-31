<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Scan;
use Domain\Entities\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Types;

interface ScansRepositoryInterface
{
	public function add(Scan $scan);

	public function persist(Scan $scan);

	public function findLastUserCheckIn(User $user);

	public function findCheckInsByDate(\Datetime $datetime);

	public function delete(Scan $scan);
}