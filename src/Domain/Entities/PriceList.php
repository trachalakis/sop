<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\PriceListsRepository')]
#[ORM\Table(name: 'price_lists')]
class PriceList
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'boolean', name: 'is_archived')]
    private bool $isArchived;

    #[ORM\Column(type: 'string', name: 'name')]
    private string $name;


    public function getId(): int
    {
        return $this->id;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getIsArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setIsArchived(bool $isArchived): void
    {
        $this->isArchived = $isArchived;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}