<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Embeddable */
class EmailAddress
{
	/**
	 * @ORM\Column(type="string", name="email_address")
	 */
	private string $emailAddress;

	public function __construct(string $emailAddress)
	{
		if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
			throw new \InvalidArgumentException();
		}

		$this->emailAddress = $emailAddress;
	}

	public function __toString()
	{
		return $this->emailAddress;
	}
}