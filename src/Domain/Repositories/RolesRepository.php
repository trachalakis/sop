<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Role;
use Doctrine\ORM\EntityRepository;

class RolesRepository extends EntityRepository
{
    public function persist(Role $role): void
    {
        $this->getEntityManager()->persist($role);
        $this->getEntityManager()->flush();
    }

    public function delete(Role $role): void
    {
        $this->getEntityManager()->remove($role);
        $this->getEntityManager()->flush();
    }
}
