<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Language;

interface LanguagesRepositoryInterface
{
	public function persist(Language $language);

	public function delete(Language $language);
}