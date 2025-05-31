<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\Supply;
use Domain\Entities\Invoice;

/**
 * @Entity
 * @Table(name="invoice_entries")
 **/
class InvoiceEntry
{
	/**
     * @Id
     * @Column(type="integer", name="id")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="float", name="price")
     */
    private float $price;

    /**
     * @ManyToOne(targetEntity="Invoice", inversedBy="invoiceEntries")
     * @JoinColumn(name="invoice_id", referencedColumnName="id")
     */
    private Invoice $invoice;

	/**
     * @Column(type="float", name="quantity")
     */
    private float $quantity;

    /**
     * @ManyToOne(targetEntity="Supply", cascade={"persist"})
     * @JoinColumn(name="supply_id", referencedColumnName="id")
     */
    private Supply $supply;

    /**
     * @Column(type="string", name="unit")
     */
    private string $unit;

    /**
     * @Column(type="float", name="vat")
     */
    private float $vat;

     /**
     * @Column(type="float", name="vat_percentage")
     */
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