<?php

declare(strict_types=1);

namespace Domain\Entities;

use Datetime;
use Domain\Entities\Order;
use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\OrderEntryGroupsRepository;

#[ORM\Entity(repositoryClass: OrderEntryGroupsRepository::class)]
#[ORM\Table(name: 'order_entry_groups')]
class OrderEntryGroup
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    private Datetime $createdAt;

    #[ORM\Column(type: 'string', name: 'notes')]
    private string $notes;

    #[ORM\OneToMany(targetEntity: 'OrderEntry', mappedBy: 'orderEntryGroup', cascade: ['persist'], orphanRemoval: true)]
    private $orderEntries;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'orderEntryGroups')]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id')]
    private Order $order;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedAt(): Datetime
    {
        return $this->createdAt;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getOrderEntries()
    {
        return $this->orderEntries;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function setCreatedAt(Datetime $createdAt): void
    {
    	$this->createdAt = $createdAt;
    }

    public function setNotes(string $notes): void
    {
    	$this->notes = $notes;
    }

    public function setOrderEntries($orderEntries): void
    {
    	$this->orderEntries = $orderEntries;
    }

    public function setOrder(Order $order): void
    {
    	$this->order = $order;
    }
}