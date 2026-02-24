<?php

declare(strict_types=1);

namespace Domain\Entities;

use Datetime;
use Domain\Entities\Supply;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\ShoppingListsRepository')]
#[ORM\Table(name: 'shopping_lists')]
class ShoppingList
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'date', name: 'date')]
    private Datetime $date;

    #[ORM\OneToMany(targetEntity: ShoppingListItem::class, mappedBy: 'shoppingList', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $shoppingListItems;

   	public function __construct()
    {
        $this->shoppingListItems = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function supplyQuantity(Supply $supply)
    {
    	foreach($this->shoppingListItems as $shoppingListItem) {
    		if ($supply == $shoppingListItem->getSupply()) {
    			return $shoppingListItem->getQuantity();
    		}
    	}

    	return 0;
    }

	public function getDate(): Datetime
    {
        return $this->date;
    }

    public function setDate(Datetime $date): void
    {
        $this->date = $date;
    }

    public function getShoppingListItems()
    {
        return $this->shoppingListItems;
    }

    public function setShoppingListItems(Collection $shoppingListItems)
    {
        $this->shoppingListItems = $shoppingListItems;
    }

}