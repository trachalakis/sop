<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\MenuItem;
use Domain\Entities\Supply;
use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\MenuItemIngredientsRepository;

#[ORM\Entity(repositoryClass: MenuItemIngredientsRepository::class)]
#[ORM\Table(name: 'menu_item_ingredients')]
class MenuItemIngredient
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Supply::class)]
    #[ORM\JoinColumn(name: 'supply_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Supply $supply;

    #[ORM\ManyToOne(targetEntity: MenuItem::class, inversedBy: 'ingredients')]
    #[ORM\JoinColumn(name: 'menu_item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private MenuItem $menuItem;

    #[ORM\Column(type: 'float', name: 'quantity')]
    private float $quantity;

    public function getSupply(): Supply
    {
    	return $this->supply;
    }

    public function getMenuItem(): MenuItem
    {
    	return $this->menuItem;
    }

    public function getQuantity(): float
    {
    	return $this->quantity;
    }

    public function setSupply(Supply $supply): void
    {
    	$this->supply = $supply;
    }

    public function setMenuItem(MenuItem $menuItem): void
    {
    	$this->menuItem = $menuItem;
    }    

    public function setQuantity(float $quantity): void
    {
    	$this->quantity = $quantity;
    }
}