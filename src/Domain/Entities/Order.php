<?php

declare(strict_types=1);

namespace Domain\Entities;

use Datetime;
use Domain\Entities\Reservation;
use Domain\Entities\Table;
use Domain\Entities\User;

/**
 * @Entity (repositoryClass="Domain\Repositories\OrdersRepository")
 * @Table(name="orders")
 **/
class Order
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
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="employee_id", referencedColumnName="id")
     */
    private ?User $employee;

    /**
     * @Column(type="integer", name="minors")
     */
    private int $minors;

	/**
     * @Column(type="string", name="notes")
     */
    private string $notes;

    /**
     * @OneToMany(targetEntity="OrderEntry", mappedBy="order", cascade={"persist"}, orphanRemoval=true)
     */
    private $orderEntries;

    /**
     * @OneToMany(targetEntity="OrderEntryGroup", mappedBy="order", cascade={"persist"}, orphanRemoval=true)
     * @OrderBy({"createdAt" = "DESC"})
     */
    private $orderEntryGroups;

    /**
     * @Column(type="datetime", name="paid_at")
     */
    private ?Datetime $paidAt;

    /*
     * @Column(type="string", name="payment_method")
     *
    private string $paymentMethod;*/

    /**
     * @Column(type="string", name="status")
     */
    private string $status;

    /**
     * @OneToOne(targetEntity="Reservation")
     * @JoinColumn(name="reservation_id", referencedColumnName="id")
     */
    private ?Reservation $reservation;

    /**
     * @ManyToOne(targetEntity="Table", inversedBy="orders")
     * @JoinColumn(name="table_id", referencedColumnName="id")
     */
    private ?Table $table;

    /**
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    private User $waiter;

    /**
     * @Column(type="string", name="uuid")
     */
    private string $uuid;

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

    public function getEmployee(): ?User
    {
        return $this->employee;
    }

    public function isPaid()
    {
    	foreach($this->orderEntries as $orderEntry) {
    		if (!$orderEntry->getIsPaid()) {
    			return false;
    		}
    	}

    	return true;
    }

    public function getMinors(): int
    {
    	return $this->minors;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getOrderEntries()
    {
        return $this->orderEntries;
    }

    public function getOrderEntryGroups()
    {
        return $this->orderEntryGroups;
    }

    public function getPaidAt(): ?Datetime
    {
    	return $this->paidAt;
    }

    public function getPaymentMethod()
   	{
   		$cashEntries = 0;
   		$creditCardEntries = 0;
   		foreach($this->orderEntries as $orderEntry) {
   			if ($orderEntry->getPaymentMethod() == 'CASH') {
   				$cashEntries++;
   			}
   			if ($orderEntry->getPaymentMethod() == 'CREDIT_CARD') {
   				$creditCardEntries++;
   			}
   		}

   		if ($cashEntries == count($this->orderEntries)) {
   			return 'CASH';
   		} else if ($creditCardEntries == count($this->orderEntries)) {
   			return 'CREDIT_CARD';
   		} else {
   			return 'BOTH';
   		}
   	}

   	public function getPrice(): float
    {
    	$cost = 0;
    	foreach ($this->getOrderEntries() as $entry) {
    		$cost += $entry->getPrice();
    	}

    	return round($cost, 1);
    }

    public function getStatus(): string
    {
    	return $this->status;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function getTable(): ?Table
    {
        return $this->table;
    }

    public function getUuid(): string
    {
    	return $this->uuid;
    }

    public function getWaiter(): User
    {
    	return $this->waiter;
    }

    public function hasCancelledEntries()
    {
        foreach($this->orderEntries as $orderEntry) {
            if (count($orderEntry->getOrderEntryCancellations()) > 0) {
                return true;
            }
        }

        return false;
    }

    public function hasDiscountedEntries()
    {
        foreach($this->orderEntries as $orderEntry) {
            if ($orderEntry->getDiscount() > 0) {
                return true;
            }
        }

        return false;
    }

    public function isDrinksOnly(): bool
    {
        foreach($this->orderEntries as $orderEntry) {
            if (!$orderEntry->getMenuItem()->getIsDrink()) {
                return false;
            }
        }

        return true;
    }

    public function setAdults(int $adults): void
    {
    	$this->adults = $adults;
    }

    public function setCreatedAt(Datetime $createdAt): void
    {
    	$this->createdAt = $createdAt;
    }

    public function setEmployee(?User $employee)
    {
        $this->employee = $employee;
    }

    public function setMinors(int $minors): void
    {
    	$this->minors = $minors;
    }

    public function setNotes(string $notes): void
    {
    	$this->notes = $notes;
    }

    public function setOrderEntries($orderEntries): void
    {
    	$this->orderEntries = $orderEntries;
    }

    public function setOrderEntryGroups($orderEntryGroups): void
    {
        $this->orderEntryGroups = $orderEntryGroups;
    }

    public function setPaidAt(?Datetime $paidAt): void
    {
    	$this->paidAt = $paidAt;
    }

   	public function setStatus(string $status): void
    {
    	$this->status = $status;
    }

    public function setReservation(?Reservation $reservation): void
    {
        $this->reservation = $reservation;
    }

    public function setTable(?Table $table): void
    {
    	$this->table = $table;
    }

    public function setUuid(string $uuid): void
    {
    	$this->uuid = $uuid;
    }

    public function setWaiter(User $waiter): void
    {
    	$this->waiter = $waiter;
    }
}