<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'take_out_request_entries')]
class TakeOutRequestEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: TakeOutRequest::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id')]
    private TakeOutRequest $request;

    #[ORM\ManyToOne(targetEntity: MenuItem::class)]
    #[ORM\JoinColumn(name: 'menu_item_id', referencedColumnName: 'id')]
    private MenuItem $menuItem;

    #[ORM\Column(type: 'float', name: 'menu_item_price')]
    private float $menuItemPrice;

    #[ORM\Column(type: 'integer', name: 'quantity')]
    private int $quantity;

    #[ORM\OneToMany(targetEntity: TakeOutRequestEntryExtra::class, mappedBy: 'entry', cascade: ['persist'], orphanRemoval: true)]
    private $extras;

    public function __construct()
    {
        $this->extras = new ArrayCollection;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRequest(): TakeOutRequest
    {
        return $this->request;
    }

    public function getMenuItem(): MenuItem
    {
        return $this->menuItem;
    }

    public function getMenuItemPrice(): float
    {
        return $this->menuItemPrice;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getExtras()
    {
        return $this->extras;
    }

    public function getPrice(): float
    {
        $price = $this->menuItemPrice;

        foreach ($this->extras as $extra) {
            $price += $extra->getPrice();
        }

        return round($this->quantity * $price, 1);
    }

    public function setRequest(TakeOutRequest $request): void
    {
        $this->request = $request;
    }

    public function setMenuItem(MenuItem $menuItem): void
    {
        $this->menuItem = $menuItem;
    }

    public function setMenuItemPrice(float $menuItemPrice): void
    {
        $this->menuItemPrice = $menuItemPrice;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function setExtras($extras): void
    {
        $this->extras = $extras;
    }
}
