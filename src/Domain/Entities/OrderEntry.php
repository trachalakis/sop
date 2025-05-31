<?php

declare(strict_types=1);

namespace Domain\Entities;

use Datetime;
use Domain\Entities\Order;
use Domain\Entities\OrderEntryGroup;
use Domain\Entities\MenuItem;

/**
 * @Entity (repositoryClass="Domain\Repositories\OrderEntriesRepository")
 * @Table(name="order_entries")
 **/
class OrderEntry
{
	/**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="datetime", name="created_at")
     */
    private ?Datetime $createdAt;

    /**
     * @Column(type="float", name="discount")
     */
    private float $discount;

    /**
     * @Column(type="string", name="discount_reason")
     */
    private ?string $discountReason;

    /**
     * @Column(type="integer", name="family")
     */
    private int $family;

    /**
     * @Column(type="boolean", name="is_paid")
     */
    private bool $isPaid;

    /**
     * @Column(type="integer", name="max_quantity")
     */
    private ?int $maxQuantity;

    /**
     * @Column(type="integer", name="max_weight")
     */
    private ?int $maxWeight;

    /**
     * @OneToOne(targetEntity="MenuItem")
     * @JoinColumn(name="menu_item_id", referencedColumnName="id")
     */
    private MenuItem $menuItem;

    /**
     * @Column(type="float", name="menu_item_price")
     */
    private float $menuItemPrice;

    /**
     * @Column(type="text", name="notes")
     */
    private string $notes;

    /**
     * @ManyToOne(targetEntity="Order", inversedBy="orderEntries")
     * @JoinColumn(name="order_id", referencedColumnName="id")
     */
    private Order $order;

    /**
     * @ManyToOne(targetEntity="OrderEntryGroup", inversedBy="orderEntries")
     * @JoinColumn(name="order_entry_group_id", referencedColumnName="id")
     */
    private ?OrderEntryGroup $orderEntryGroup;

    /**
     * @OneToMany(targetEntity="OrderEntryCancellation", mappedBy="orderEntry", cascade={"persist"}, orphanRemoval=true)
     */
    private $orderEntryCancellations;

    /**
     * @OneToMany(targetEntity="OrderEntryExtra", mappedBy="orderEntry", cascade={"persist"}, orphanRemoval=true)
     */
    private $orderEntryExtras;

    /**
     * @Column(type="float", name="price")
     */
    private float $price;

    /**
     * @Column(type="integer", name="quantity")
     */
    private int $quantity;

    /**
     * @Column(type="integer", name="timing")
     */
    private int $timing;

    /**
     * @Column(type="integer", name="weight")
     */
    private ?int $weight;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?Datetime
    {
        return $this->createdAt;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function getDiscountReason(): ?string
    {
        return $this->discountReason;
    }

    public function getFamily(): int
    {
        return $this->family;
    }

    //alias for graph-ql
    public function getIsDeleted(): bool
    {
    	return $this->isDeleted();
    }

    public function getIsPaid(): bool
    {
    	return $this->isPaid || $this->getPrice() == 0;
    }

    public function getMaxQuantity(): ?int
    {
    	return $this->maxQuantity;
    }

    public function getMaxWeight(): ?int
    {
    	return $this->maxWeight;
    }

    public function getMenuItem(): MenuItem
    {
        return $this->menuItem;
    }

    public function getMenuItemPrice(): float
    {
        return $this->menuItemPrice;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getOrderEntryGroup(): ?OrderEntryGroup
    {
        return $this->orderEntryGroup;
    }

    public function getOrderEntryCancellations()
    {
        return $this->orderEntryCancellations;
    }

    public function getOrderEntryExtras()
    {
        return $this->orderEntryExtras;
    }

    public function getPrice(): float
    {
        //hack for 2023 order entry format
        if ($this->getCreatedAt() != null && $this->getCreatedAt()->format('Y') == 2023) {
            return $this->price;
        }

        $price = $this->menuItemPrice;

        if ($this->weight != null) {
            if ($this->weight < 1000) {
                $price -= ($price * ((1000 - $this->weight) / 1000));
            } else {
                $price += $price * (($this->weight - 1000) / 1000);
            }
        }

        foreach($this->orderEntryExtras as $orderEntryExtra) {
            $price = $price + $orderEntryExtra->getPrice();
        }

        return round(($this->quantity * $price) - $this->discount, 1);
    }

    public function getQuantity(): int
    {
    	return $this->quantity;
    }

    public function getTiming(): int
    {
        return $this->timing;
    }

    public function getWeight(): ?int
    {
    	return $this->weight;
    }

    /*public function isDeleted(): bool
    {
    	return $this->quantity <= 0;
    }*/

    public function setCreatedAt(?Datetime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setDiscount(float $discount): void
    {
    	$this->discount = $discount;
    }

    public function setDiscountReason(?string $discountReason): void
    {
        $this->discountReason = $discountReason;
    }

    public function setFamily(int $family): void
    {
    	$this->family = $family;
    }

    public function setIsPaid(bool $isPaid): void
    {
    	$this->isPaid = $isPaid;
    }

    public function setMaxQuantity(int $maxQuantity): void
    {
    	$this->maxQuantity = $maxQuantity;
    }

    public function setMaxWeight(?int $maxWeight): void
    {
    	$this->maxWeight = $maxWeight;
    }

    public function setMenuItem(MenuItem $menuItem): void
    {
    	$this->menuItem = $menuItem;
    }

    public function setMenuItemPrice(float $menuItemPrice): void
    {
        $this->menuItemPrice = $menuItemPrice;
    }

    public function setNotes(string $notes): void
    {
    	$this->notes = $notes;
    }

    public function setOrder(Order $order): void
    {
    	$this->order = $order;
    }

    public function setOrderEntryGroup(?OrderEntryGroup $orderEntryGroup): void
    {
        $this->orderEntryGroup = $orderEntryGroup;
    }

    public function setOrderEntryCancellations($orderEntryCancellations): void
    {
        $this->orderEntryCancellations = $orderEntryCancellations;
    }

    public function setOrderEntryExtras($orderEntryExtras): void
    {
    	$this->orderEntryExtras = $orderEntryExtras;
    }

    public function setPrice(float $price): void
    {
    	$this->price = $price;
    }

    public function setQuantity(int $quantity): void
    {
    	$this->quantity = $quantity;
    }

    public function setTiming(int $timing): void
    {
    	$this->timing = $timing;
    }

    public function setWeight(?int $weight): void
    {
    	$this->weight = $weight;
    }
}