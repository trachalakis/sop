<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTimeImmutable;
use Domain\Enums\TakeOutRequestStatus;
use Domain\Repositories\TakeOutRequestsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TakeOutRequestsRepository::class)]
#[ORM\Table(name: 'take_out_requests')]
class TakeOutRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', name: 'token', unique: true)]
    private string $token;

    #[ORM\Column(type: 'string', name: 'customer_name')]
    private string $customerName;

    #[ORM\Column(type: 'string', name: 'customer_phone')]
    private string $customerPhone;

    #[ORM\Column(type: 'text', name: 'notes')]
    private string $notes;

    #[ORM\Column(type: 'string', enumType: TakeOutRequestStatus::class, name: 'status')]
    private TakeOutRequestStatus $status;

    #[ORM\Column(type: 'integer', name: 'eta_minutes', nullable: true)]
    private ?int $etaMinutes;

    #[ORM\Column(type: 'datetime_immutable', name: 'responded_at', nullable: true)]
    private ?DateTimeImmutable $respondedAt;

    #[ORM\OneToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: true)]
    private ?Order $order;

    #[ORM\OneToMany(targetEntity: TakeOutRequestEntry::class, mappedBy: 'request', cascade: ['persist'], orphanRemoval: true)]
    private $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCustomerPhone(): string
    {
        return $this->customerPhone;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getStatus(): TakeOutRequestStatus
    {
        return $this->status;
    }

    public function getEtaMinutes(): ?int
    {
        return $this->etaMinutes;
    }

    public function getRespondedAt(): ?DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getEntries()
    {
        return $this->entries;
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->entries as $entry) {
            $total += $entry->getPrice();
        }

        return round($total, 1);
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function setCustomerName(string $customerName): void
    {
        $this->customerName = $customerName;
    }

    public function setCustomerPhone(string $customerPhone): void
    {
        $this->customerPhone = $customerPhone;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function setStatus(TakeOutRequestStatus $status): void
    {
        $this->status = $status;
    }

    public function setEtaMinutes(?int $etaMinutes): void
    {
        $this->etaMinutes = $etaMinutes;
    }

    public function setRespondedAt(?DateTimeImmutable $respondedAt): void
    {
        $this->respondedAt = $respondedAt;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function setEntries($entries): void
    {
        $this->entries = $entries;
    }
}
