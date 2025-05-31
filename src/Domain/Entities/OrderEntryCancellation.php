<?php

declare(strict_types=1);

namespace Domain\Entities;

use Datetime;
use Domain\Entities\Order;
use Domain\Entities\OrderEntryGroup;
use Domain\Entities\MenuItem;

/**
 * @Entity (repositoryClass="Domain\Repositories\OrderEntryCancellationsRepository")
 * @Table(name="order_entry_cancellations")
 **/
class OrderEntryCancellation
{
	/**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="text", name="cancellation_reason")
     */
    private string $cancellationReason;

    /**
     * @Column(type="datetime", name="created_at")
     */
    private ?Datetime $createdAt;

    /**
     * @OneToOne(targetEntity="OrderEntry")
     * @JoinColumn(name="order_entry_id", referencedColumnName="id")
     */
    private OrderEntry $orderEntry;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCancellationReason(): string
    {
        return $this->cancellationReason;
    }

    public function getCreatedAt(): ?Datetime
    {
        return $this->createdAt;
    }

    public function getOrderEntry(): OrderEntry
    {
        return $this->orderEntry;
    }

    public function setCancellationReason(string $cancellationReason): void
    {
        $this->cancellationReason = $cancellationReason;
    }

    public function setCreatedAt(?Datetime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setOrderEntry(OrderEntry $orderEntry): void
    {
        $this->orderEntry = $orderEntry;
    }
}