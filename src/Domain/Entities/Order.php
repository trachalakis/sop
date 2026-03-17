<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTimeImmutable;
use Domain\Entities\Reservation;
use Domain\Entities\Table;
use Domain\Entities\User;
use Domain\Repositories\OrdersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrdersRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'integer', name: 'adults')]
    private int $adults;

    #[ORM\Column(type: 'datetimetz_immutable', name: 'created_at')]
    private DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'id')]
    private ?User $employee;

    #[ORM\Column(type: 'integer', name: 'minors')]
    private int $minors;

    #[ORM\Column(type: 'string', name: 'notes')]
    private string $notes;

    #[ORM\OneToMany(targetEntity: OrderEntry::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    private $orderEntries;

    #[ORM\OneToMany(targetEntity: OrderEntryGroup::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private $orderEntryGroups;

    #[ORM\Column(type: 'datetimetz_immutable', name: 'paid_at')]
    private ?DateTimeImmutable $paidAt;

    #[ORM\Column(type: 'string', name: 'status')]
    private string $status;

    #[ORM\OneToOne(targetEntity: Reservation::class)]
    #[ORM\JoinColumn(name: 'reservation_id', referencedColumnName: 'id')]
    private ?Reservation $reservation;

    #[ORM\ManyToOne(targetEntity: Table::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(name: 'table_id', referencedColumnName: 'id')]
    private ?Table $table;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $waiter;

    #[ORM\Column(type: 'string', name: 'uuid')]
    private string $uuid;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAdults(): int
    {
        return $this->adults;
    }

    public function getCreatedAt(): DateTimeImmutable
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

    public function getPaidAt(): ?DateTimeImmutable
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

    public function setCreatedAt(DateTimeImmutable $createdAt): void
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

    public function setPaidAt(?DateTimeImmutable $paidAt): void
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