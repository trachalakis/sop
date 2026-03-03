<?php

declare(strict_types=1);

namespace Domain\Entities;

use Datetime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\ReservationsRepository')]
#[ORM\Table(name: 'reservations')]
class Reservation
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'integer', name: 'adults')]
    private int $adults;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    private Datetime $createdAt;

    #[ORM\Column(type: 'datetime', name: 'date_time')]
    private Datetime $dateTime;

    #[ORM\Column(type: 'string', name: 'email_address')]
    private ?string $emailAddress;

    #[ORM\Column(type: 'string', name: 'comments')]
    private ?string $comments;

    #[ORM\Column(type: 'boolean', name: 'is_table_locked')]
    private bool $isTableLocked;

    #[ORM\Column(type: 'integer', name: 'minors')]
    private int $minors;

    #[ORM\Column(type: 'string', name: 'name')]
    private string $name;

    #[ORM\Column(type: 'string', name: 'status')]
    private string $status;

    #[ORM\Column(type: 'simple_array', name: 'tables')]
    private array $tables;

    #[ORM\Column(type: 'string', name: 'telephone_number')]
    private string $telephoneNumber;

    public function getId(): int
    {
        return $this->id;
    }

	public function getAdults(): int
    {
    	return $this->adults;
    }

    public function getCreatedAt(): Datetime
    {
        return $this->createdAt;
    }

    public function getDateTime(): Datetime
    {
        return $this->dateTime;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function getIsTableLocked(): bool
    {
        return $this->isTableLocked;
    }

    public function getMinors(): int
    {
    	return $this->minors;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): string
    {
    	return $this->status;
    }

    public function getTables(): array
    {
        return $this->tables;
    }

    public function getTelephonenumber(): string
    {
    	return $this->telephoneNumber;
    }

    public function setAdults(int $adults): void
    {
    	$this->adults = $adults;
    }

    public function setCreatedAt(Datetime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setDateTime(Datetime $dateTime): void
    {
        $this->dateTime = $dateTime;
    }

    public function setEmailAddress(?string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function setComments(?string $comments): void
    {
        $this->comments = $comments;
    }

    public function setIsTableLocked(bool $isTableLocked): void
    {
        $this->isTableLocked = $isTableLocked;
    }

    public function setMinors(int $minors): void
    {
    	$this->minors = $minors;
    }

	public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setStatus(string $status): void
    {
    	$this->status = $status;
    }

    public function setTables(array $tables): void
    {
        $this->tables = $tables;
    }

    public function setTelephoneNumber(string $telephoneNumber): void
    {
    	$this->telephoneNumber = $telephoneNumber;
    }
}