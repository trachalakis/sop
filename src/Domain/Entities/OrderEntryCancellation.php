<?php

declare(strict_types=1);

namespace Domain\Entities;

use Datetime;
use Domain\Entities\OrderEntry;
use Domain\Repositories\OrderEntryCancellationsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderEntryCancellationsRepository::class)]
#[ORM\Table(name: 'order_entry_cancellations')]
class OrderEntryCancellation
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'text', name: 'cancellation_reason')]
    private string $cancellationReason;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    private ?Datetime $createdAt;

    #[ORM\OneToOne(targetEntity: OrderEntry::class)]
    #[ORM\JoinColumn(name: 'order_entry_id', referencedColumnName: 'id')]
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