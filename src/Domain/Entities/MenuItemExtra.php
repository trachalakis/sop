<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\MenuItem;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\MenuItemExtrasRepository')]
#[ORM\Table(name: 'menu_item_extras')]
class MenuItemExtra
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string', name: 'name')]
    private string $name;

    #[ORM\Column(type: 'float', name: 'price')]
    private float $price;

    #[ORM\ManyToOne(targetEntity: MenuItem::class, inversedBy: 'menuItemExtras')]
    #[ORM\JoinColumn(name: 'menu_item_id', referencedColumnName: 'id')]
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