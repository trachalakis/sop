<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'take_out_request_entry_extras')]
class TakeOutRequestEntryExtra
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: TakeOutRequestEntry::class, inversedBy: 'extras')]
    #[ORM\JoinColumn(name: 'entry_id', referencedColumnName: 'id')]
    private TakeOutRequestEntry $entry;

    #[ORM\Column(type: 'string', name: 'name')]
    private string $name;

    #[ORM\Column(type: 'float', name: 'price')]
    private float $price;

    public function getId(): int
    {
        return $this->id;
    }

    public function getEntry(): TakeOutRequestEntry
    {
        return $this->entry;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setEntry(TakeOutRequestEntry $entry): void
    {
        $this->entry = $entry;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }
}
