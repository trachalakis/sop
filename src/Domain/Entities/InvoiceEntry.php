<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\Supply;
use Domain\Entities\Invoice;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_entries')]
class InvoiceEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'id')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'float', name: 'price')]
    private float $price;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'invoiceEntries')]
    #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id')]
    private Invoice $invoice;

    #[ORM\Column(type: 'float', name: 'quantity')]
    private float $quantity;

    #[ORM\ManyToOne(targetEntity: Supply::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'supply_id', referencedColumnName: 'id')]
    private Supply $supply;

    #[ORM\Column(type: 'string', name: 'unit')]
    private string $unit;

    #[ORM\Column(type: 'float', name: 'vat')]
    private float $vat;

    #[ORM\Column(type: 'float', name: 'vat_percentage')]
    private $vatPercentage;

    public function getId()
    {
        return $this->id;
    }

    public function getInvoice()
    {
        return $this->invoice;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getQuantity(): float
    {
    	return $this->quantity;
    }

    public function getSupply(): Supply
    {
        return $this->supply;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getVat(): float
    {
        return $this->vat;
    }

    public function getVatPercentage(): float
    {
        return $this->vatPercentage;
    }

    public function setInvoice(Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function setQuantity(float $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function setSupply(Supply $supply): void
    {
        $this->supply = $supply;
    }

    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }

    public function setVat(float $vat): void
    {
        $this->vat = $vat;
    }

    public function setVatPercentage(float $vatPercentage): void
    {
        $this->vatPercentage = $vatPercentage;
    }
}