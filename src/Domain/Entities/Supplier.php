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

    #[ORM\Column(type: 'string', unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $details = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setTelephone(?string $telephone): void
    {
        $this->telephone = $telephone;
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
