<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\ActivityLog;
use Doctrine\ORM\EntityRepository;

class ActivityLogRepository extends EntityRepository
{
	public function persist(ActivityLog $activityLog)
	{
		$this->getEntityManager()->persist($activityLog);
	   	$this->getEntityManager()->flush();
	}

	public function delete(ActivityLog $activityLog)
	{
		$this->getEntityManager()->remove($activityLog);
	   	$this->getEntityManager()->flush();
	}
}