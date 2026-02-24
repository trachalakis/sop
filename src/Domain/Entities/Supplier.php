<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\EmailAddress;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\SuppliersRepository')]
#[ORM\Table(name: 'suppliers')]
class Supplier
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'id')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string', name: 'address')]
    private ?string $address;

    #[ORM\Column(type: 'string', name: 'email_address')]
    private ?string $emailAddress;

    #[ORM\Column(type: 'string', name: 'name', unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', name: 'occupation')]
    private ?string $occupation;

    #[ORM\Column(type: 'string', name: 'tax_office')]
    private ?string $taxOffice;

    #[ORM\Column(type: 'string', name: 'tax_registration_number')]
    private ?string $taxRegistrationNumber;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

	public function getName(): string
    {
        return $this->name;
    }

    public function getOccupation(): ?string
    {
        return $this->occupation;
    }

    public function getTaxOffice(): ?string
    {
        return $this->taxOffice;
    }

    public function getTaxRegistrationNumber(): ?string
    {
        return $this->taxRegistrationNumber;;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function setEmailAddress(?string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setOccupation(string $occupation): void
    {
        $this->occupation = $occupation;
    }

    public function setTaxOffice(string $taxOffice): void
    {
        $this->taxOffice = $taxOffice;
    }

    public function setTaxRegistrationNumber(string $taxRegistrationNumber): void
    {
        $this->taxRegistrationNumber = $taxRegistrationNumber;
    }
}