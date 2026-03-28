<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\ShoppingListsRepository;

#[ORM\Entity(repositoryClass: ShoppingListsRepository::class)]
#[ORM\Table(name: 'shopping_lists')]
class ShoppingList
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'date_immutable', name: 'date', unique: true)]
    private DateTimeImmutable $date;

    #[ORM\Column(type: 'datetimetz_immutable', name: 'created_at')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetimetz_immutable', name: 'updated_at')]
    private DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(targetEntity: ShoppingListEntry::class, mappedBy: 'shoppingList', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function setDate(DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function clearEntries(): void
    {
        $this->entries->clear();
    }

    public function addEntry(ShoppingListEntry $entry): void
    {
        $this->entries->add($entry);
    }
}
