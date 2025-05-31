<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\OrderEntry;

/**
 * @Entity
 * @Table(name="order_entry_extras")
 **/
class OrderEntryExtra
{
	/**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="string")
     */
    private string $name;

	/**
     * @Column(type="float")
     */
    private float $price;

    /**
     * @ManyToOne(targetEntity="OrderEntry", inversedBy="orderEntryExtras")
     * @JoinColumn(name="order_entry_id", referencedColumnName="id")
     */
    private OrderEntry $orderEntry;

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOrderEntry()
    {
        return $this->orderEntry;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function setOrderEntry(OrderEntry $orderEntry)
    {
        $this->orderEntry = $orderEntry;
    }
}