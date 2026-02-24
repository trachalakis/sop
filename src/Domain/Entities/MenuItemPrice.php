<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\MenuItem;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\MenuItemPricesRepository')]
#[ORM\Table(name: 'menu_item_prices')]
class MenuItemPrice
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: MenuItem::class, inversedBy: 'menuItemPrices')]
    #[ORM\JoinColumn(name: 'menu_item_id', referencedColumnName: 'id')]
    private MenuItem $menuItem;

    #[ORM\Column(type: 'float', name: 'price')]
    private float $price;

    #[ORM\ManyToOne(targetEntity: PriceList::class, inversedBy: 'menuItemPrices')]
    #[ORM\JoinColumn(name: 'price_list_id', referencedColumnName: 'id')]
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