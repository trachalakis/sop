<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Language;
use Doctrine\ORM\EntityRepository;

class LanguagesRepository extends EntityRepository
{
	public function persist(Language $language)
	{
        $this->getEntityManager()->persist($language);
       	$this->getEntityManager()->flush();
	}

	public function delete(Language $language)
	{
        $this->getEntityManager()->remove($language);
       	$this->getEntityManager()->flush();
	}
}