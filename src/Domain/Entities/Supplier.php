<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\SuppliersRepository;

#[ORM\Entity(repositoryClass: SuppliersRepository::class)]
#[ORM\Table(name: 'suppliers')]
class Supplier
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', unique: true)]
    private string $name;

    #[ORM\Column(name: 'telephone_number', type: 'string', nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(name: 'details', type: 'json', nullable: true)]
    private ?array $details = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTelephoneNumber(): ?string
    {
        return $this->telephoneNumber;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setTelephone(?string $telephoneNumber): void
    {
        $this->telephoneNumber = $telephoneNumber;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(?array $details): void
    {
        $this->details = $details;
    }
}
