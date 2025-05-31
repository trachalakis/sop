<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\ShoppingList;
use Domain\Entities\Supply;

/**
 * @Entity
 * @Table(name="shopping_list_items")
 **/
class ShoppingListItem
{
    /**
     * @Column(type="float", name="quantity")
     */
    private float $quantity;

    /**
     * @Id
     * @ManyToOne(targetEntity="ShoppingList", inversedBy="shoppingListItems")
     * @JoinColumn(name="shopping_list_id", referencedColumnName="id")
     */
    private ShoppingList $shoppingList;

    /**
     * @Id
     * @OneToOne(targetEntity="Supply")
     * @JoinColumn(name="supply_id", referencedColumnName="id")
     */
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