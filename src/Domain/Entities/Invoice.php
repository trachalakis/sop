<?php

declare(strict_types=1);

namespace Domain\Entities;

use Datetime;
use Domain\Entities\Supplier;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\InvoicesRepository')]
#[ORM\Table(name: 'invoices')]
class Invoice
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'id')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'text', name: 'comments')]
    private ?string $comments;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    private Datetime $createdAt;

    #[ORM\Column(type: 'date', name: 'date')]
    private Datetime $date;

    #[ORM\OneToMany(targetEntity: InvoiceEntry::class, mappedBy: 'invoice', cascade: ['persist'], orphanRemoval: true)]
    private $invoiceEntries;

    #[ORM\Column(type: 'string', name: 'invoice_number')]
    private ?string $invoiceNumber = null;

    #[ORM\ManyToOne(targetEntity: Supplier::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'supplier_id', referencedColumnName: 'id')]
    private Supplier $supplier;

    #[ORM\Column(type: 'float', name: 'total')]
    private ?float $total;

    #[ORM\Column(type: 'string', name: 'type')]
    private string $type;

    #[ORM\Column(type: 'datetime', name: 'updated_at')]
    private Datetime $updatedAt;

    #[ORM\Column(type: 'float', name: 'vat')]
    private ?float $vat;

    public function __construct()
    {
        //$this->invoiceEntries = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCommnents(): ?string
    {
        return $this->comments;
    }

    public function getCreatedAt(): Datetime
    {
        return $this->createdAt;
    }

	public function getDate(): Datetime
    {
        return $this->date;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function getInvoiceEntries()
    {
    	return $this->invoiceEntries;
    }

    /*public function getInvoiceSums()
    {
        return $this->invoiceSums;
    }*/

    public function getSupplier(): Supplier
    {
    	return $this->supplier;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUpdatedAt(): Datetime
    {
        return $this->updatedAt;
    }

    public function getVat(): ?float
    {
        return $this->vat;
    }

    public function setComments(?string $comments): void
    {
        $this->comments = $comments;
    }

    public function setCreatedAt(Datetime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setDate(Datetime $date): void
    {
        $this->date = $date;
    }

    public function setInvoiceEntries($invoiceEntries): void
    {
        $this->invoiceEntries = $invoiceEntries;
    }

    public function setInvoiceNumber(?string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    public function setSupplier(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setUpdatedAt(Datetime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function setVat(float $vat): void
    {
    	$this->vat = $vat;
    }
}