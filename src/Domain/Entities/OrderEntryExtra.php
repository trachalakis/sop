<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\OrderEntry;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_entry_extras')]
class OrderEntryExtra
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'float')]
    private float $price;

    #[ORM\ManyToOne(targetEntity: OrderEntry::class, inversedBy: 'orderEntryExtras')]
    #[ORM\JoinColumn(name: 'order_entry_id', referencedColumnName: 'id')]
    private OrderEntry $orderEntry;

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOrderEntry()
    {
        return $this->orderEntry;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function setOrderEntry(OrderEntry $orderEntry)
    {
        $this->orderEntry = $orderEntry;
    }
}