<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Invoice;

interface InvoicesRepositoryInterface
{
	public function persist(Invoice $invoice);

	public function delete(Invoice $invoice);
}