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

    #[ORM\Column(name: 'series', type: 'string', length: 50, nullable: true)]
    private ?string $series = null;

    #[ORM\Column(name: 'document_type', type: 'string', length: 100, nullable: true)]
    private ?string $documentType = null;

    #[ORM\Column(name: 'mark', type: 'string', length: 50, nullable: true, unique: true)]
    private ?string $mark = null;

    #[ORM\Column(name: 'net_total', type: 'float', nullable: true)]
    private ?float $netTotal = null;

    #[ORM\Column(name: 'vat_total', type: 'float', nullable: true)]
    private ?float $vatTotal = null;

    #[ORM\Column(name: 'gross_total', type: 'float', nullable: true)]
    private ?float $grossTotal = null;

    #[ORM\Column(name: 'mydata_url', type: 'text', nullable: true)]
    private ?string $mydataUrl = null;

    #[ORM\Column(name: 'scanned_at', type: 'datetimetz')]
    private DateTimeInterface $scannedAt;

    #[ORM\OneToMany(targetEntity: InvoiceEntry::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $entries;

    public function __construct()
    {
        $this->entries   = new ArrayCollection();
        $this->scannedAt = new \DateTime();
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

    public function getSeries(): ?string
    {
        return $this->series;
    }

    public function getDocumentType(): ?string
    {
        return $this->documentType;
    }

    public function getMark(): ?string
    {
        return $this->mark;
    }

    public function getNetTotal(): ?float
    {
        return $this->netTotal;
    }

    public function getVatTotal(): ?float
    {
        return $this->vatTotal;
    }

    public function getGrossTotal(): ?float
    {
        return $this->grossTotal;
    }

    public function getMydataUrl(): ?string
    {
        return $this->mydataUrl;
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

    public function setSeries(?string $series): void
    {
        $this->series = $series;
    }

    public function setDocumentType(?string $documentType): void
    {
        $this->documentType = $documentType;
    }

    public function setMark(?string $mark): void
    {
        $this->mark = $mark;
    }

    public function setNetTotal(?float $netTotal): void
    {
        $this->netTotal = $netTotal;
    }

    public function setVatTotal(?float $vatTotal): void
    {
        $this->vatTotal = $vatTotal;
    }

    public function setGrossTotal(?float $grossTotal): void
    {
        $this->grossTotal = $grossTotal;
    }

    public function setMydataUrl(?string $mydataUrl): void
    {
        $this->mydataUrl = $mydataUrl;
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
