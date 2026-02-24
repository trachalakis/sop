<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\ShoppingList;
use Domain\Entities\Supply;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shopping_list_items')]
class ShoppingListItem
{
    #[ORM\Column(type: 'float', name: 'quantity')]
    private float $quantity;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ShoppingList::class, inversedBy: 'shoppingListItems')]
    #[ORM\JoinColumn(name: 'shopping_list_id', referencedColumnName: 'id')]
    private ShoppingList $shoppingList;

    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Supply::class)]
    #[ORM\JoinColumn(name: 'supply_id', referencedColumnName: 'id')]
    private Supply $supply;

    public function getId()
    {
        return $this->id;
    }

    public function getQuantity(): float
    {
    	return $this->quantity;
    }

    public function getSupply(): Supply
    {
    	return $this->supply;
    }

    public function setQuantity(float $quantity)
    {
        $this->quantity = $quantity;
    }

    public function setShoppingList(ShoppingList $shoppingList)
    {
        $this->shoppingList = $shoppingList;
    }

    public function setSupply(Supply $supply)
    {
        $this->supply = $supply;
    }

    public function getShoppingListItems()
    {
        return $this->shoppingListItems;
    }
}