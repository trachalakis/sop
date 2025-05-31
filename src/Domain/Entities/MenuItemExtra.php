<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\MenuItem;

/**
 * @Entity(repositoryClass="Domain\Repositories\MenuItemExtrasRepository")
 * @Table(name="menu_item_extras")
 **/
class MenuItemExtra
{
	/**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="string", name="name")
     */
    private string $name;

    /**
     * @Column(type="float", name="price")
     */
    private float $price;

    /**
     * @ManyToOne(targetEntity="MenuItem", inversedBy="menuItemExtras")
     * @JoinColumn(name="menu_item_id", referencedColumnName="id")
     */
    private MenuItem $menuItem;

    public function __construct(string $name, float $price, MenuItem $menuItem)
    {
        $this->setName($name);
        $this->setPrice($price);
        $this->setMenuItem($menuItem);
    }

    public function getId(): int
    {
        return $this->id;
    }

	public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getMenuItem(): MenuItem
    {
        return $this->menuItem;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function setMenuItem(MenuItem $menuItem): void
    {
        $this->menuItem = $menuItem;
    }
}