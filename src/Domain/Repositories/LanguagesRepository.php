<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Language;
use Domain\Repositories\LanguagesRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class LanguagesRepository extends EntityRepository implements LanguagesRepositoryInterface
{
	public function persist(Language $language)
	{
		$this->_em->persist($language);
       	$this->_em->flush();
	}

	public function delete(Language $language)
	{
		$this->_em->remove($language);
       	$this->_em->flush();
	}
}