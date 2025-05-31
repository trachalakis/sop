<?php

declare(strict_types=1);

namespace Domain\Entities;

use Datetime;
use Domain\Entities\Table;

/**
 * @Entity(repositoryClass="Domain\Repositories\ReservationsRepository")
 * @Table(name="reservations")
 **/
class Reservation
{
	/**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="integer", name="adults")
     */
    private int $adults;

	/**
     * @Column(type="datetime", name="created_at")
     */
    private Datetime $createdAt;

    /**
     * @Column(type="datetime", name="date_time")
     */
    private Datetime $dateTime;

    /**
     * @Column(type="string", name="email_address")
     */
    private ?string $emailAddress;

    /**
     * @Column(type="string", name="comments")
     */
    private ?string $comments;

    /**
     * @Column(type="integer", name="minors")
     */
    private int $minors;

    /**
     * @Column(type="string", name="name")
     */
    private string $name;

    /**
     * @Column(type="string", name="status")
     */
    private string $status;

    /**
     * @OneToOne(targetEntity="Table")
     * @JoinColumn(name="table_id", referencedColumnName="id")
     */
    private ?Table $table;

    /**
     * @Column(type="array", name="tables")
     */
    private ?array $tables;

    /**
     * @Column(type="string", name="telephone_number")
     */
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

    public function getTable(): ?Table
    {
        return $this->table;
    }

    public function getTables(): ?array
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

    public function setTable(?Table $table): void
    {
        $this->table = $table;
    }

    public function setTables(?array $tables): void
    {
        $this->tables = $tables;
    }

    public function setTelephoneNumber(string $telephoneNumber): void
    {
    	$this->telephoneNumber = $telephoneNumber;
    }
}