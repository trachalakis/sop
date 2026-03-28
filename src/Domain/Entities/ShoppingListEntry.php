<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shopping_list_entries')]
class ShoppingListEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: ShoppingList::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'shopping_list_id', referencedColumnName: 'id', nullable: false)]
    private ShoppingList $shoppingList;

    #[ORM\ManyToOne(targetEntity: Supply::class)]
    #[ORM\JoinColumn(name: 'supply_id', referencedColumnName: 'id', nullable: false)]
    private Supply $supply;

    #[ORM\Column(type: 'float', name: 'quantity')]
    private float $quantity;

    public function getId(): int
    {
        return $this->id;
    }

    public function getShoppingList(): ShoppingList
    {
        return $this->shoppingList;
    }

    public function getSupply(): Supply
    {
        return $this->supply;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function setShoppingList(ShoppingList $shoppingList): void
    {
        $this->shoppingList = $shoppingList;
    }

    public function setSupply(Supply $supply): void
    {
        $this->supply = $supply;
    }

    public function setQuantity(float $quantity): void
    {
        $this->quantity = $quantity;
    }
}
