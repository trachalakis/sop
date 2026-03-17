<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\TablesRepository;

#[ORM\Entity(repositoryClass: TablesRepository::class)]
#[ORM\Table(name: 'tables')]
class Table
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'string', name: 'name', unique: true)]
    private string $name;

    #[ORM\Column(type: 'integer', name: 'position')]
    private int $position;

    public function getId()
    {
        return $this->id;
    }

	public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

	public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}