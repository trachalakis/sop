<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\InvoicesRepository;

#[ORM\Entity(repositoryClass: InvoicesRepository::class)]
#[ORM\Table(name: 'invoices')]
class Invoice
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(name: 'supplier_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Supplier $supplier;

    #[ORM\Column(type: 'date')]
    private DateTimeInterface $date;

    #[ORM\Column(name: 'invoice_number', type: 'string', length: 100, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(name: 'scanned_at', type: 'datetimetz')]
    private DateTimeInterface $scannedAt;

    #[ORM\OneToMany(targetEntity: InvoiceEntry::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $entries;

    public function __construct()
    {
        $this->entries   = new ArrayCollection();
        $this->scannedAt = new \DateTimeImmutable();
    }

    public function getId(): int 
    { 
        return $this->id;
    }

    public function getSupplier(): Supplier 
    { 
        return $this->supplier; 
    }
    
    public function getDate(): DateTimeInterface 
    { 
        return $this->date; 
    }
    
    public function getInvoiceNumber(): ?string 
    { 
        return $this->invoiceNumber; 
    }
    
    public function getScannedAt(): DateTimeInterface 
    { 
        return $this->scannedAt; 
    }
    
    public function getEntries(): Collection 
    { 
        return $this->entries; 
    }

    public function setSupplier(Supplier $supplier): void 
    { 
        $this->supplier = $supplier; 
    }
    
    public function setDate(DateTimeInterface $date): void 
    { 
        $this->date = $date; 
    }
    
    public function setInvoiceNumber(?string $invoiceNumber): void 
    { 
        $this->invoiceNumber = $invoiceNumber; 
    }
    
    public function setScannedAt(DateTimeInterface $scannedAt): void 
    { 
        $this->scannedAt = $scannedAt; 
    }

    public function addEntry(InvoiceEntry $entry): void
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
            $entry->setInvoice($this);
        }
    }
}
