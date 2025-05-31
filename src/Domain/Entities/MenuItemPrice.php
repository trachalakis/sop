<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\MenuItem;

/**
 * @Entity(repositoryClass="Domain\Repositories\MenuItemPricesRepository")
 * @Table(name="menu_item_prices")
 **/
class MenuItemPrice
{
	/**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @ManyToOne(targetEntity="MenuItem", inversedBy="menuItemPrices")
     * @JoinColumn(name="menu_item_id", referencedColumnName="id")
     */
    private MenuItem $menuItem;

    /**
     * @Column(type="float", name="price")
     */
    private float $price;

    /**
     * @ManyToOne(targetEntity="PriceList", inversedBy="menuItemPrices")
     * @JoinColumn(name="price_list_id", referencedColumnName="id")
     */
    private PriceList $priceList;

    public function __construct(float $price, PriceList $priceList, MenuItem $menuItem)
    {
        $this->setPrice($price);
        $this->setPriceList($priceList);
        $this->setMenuItem($menuItem);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMenuItem(): MenuItem
    {
        return $this->menuItem;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getPriceList(): PriceList
    {
        return $this->priceList;
    }

    public function setMenuItem(MenuItem $menuItem): void
    {
        $this->menuItem = $menuItem;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function setPriceList(PriceList $priceList): void
    {
        $this->priceList = $priceList;
    }
}