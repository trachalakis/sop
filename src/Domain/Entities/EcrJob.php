<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTimeImmutable;
use Domain\Repositories\EcrJobsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EcrJobsRepository::class)]
#[ORM\Table(name: 'ecr_queue')]
class EcrJob
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id')]
    private Order $order;

    #[ORM\Column(type: 'string')]
    private string $status = 'pending';

    #[ORM\Column(type: 'integer')]
    private int $attempts = 0;

    #[ORM\Column(type: 'datetimetz_immutable', name: 'last_attempted_at', nullable: true)]
    private ?DateTimeImmutable $lastAttemptedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $error = null;

    #[ORM\Column(type: 'datetimetz_immutable', name: 'created_at')]
    private DateTimeImmutable $createdAt;

    public function getId(): int { return $this->id; }
    public function getOrder(): Order { return $this->order; }
    public function getStatus(): string { return $this->status; }
    public function getAttempts(): int { return $this->attempts; }
    public function getLastAttemptedAt(): ?DateTimeImmutable { return $this->lastAttemptedAt; }
    public function getError(): ?string { return $this->error; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }

    public function setOrder(Order $order): void { $this->order = $order; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setAttempts(int $attempts): void { $this->attempts = $attempts; }
    public function setLastAttemptedAt(?DateTimeImmutable $lastAttemptedAt): void { $this->lastAttemptedAt = $lastAttemptedAt; }
    public function setError(?string $error): void { $this->error = $error; }
    public function setCreatedAt(DateTimeImmutable $createdAt): void { $this->createdAt = $createdAt; }
}
