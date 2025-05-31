<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\Supplier;
use Domain\Entities\SupplyGroup;

/**
 * @Entity(repositoryClass="Domain\Repositories\SuppliesRepository")
 * @Table(name="supplies")
 **/
class Supply
{
	/**
     * @Id
     * @Column(type="integer", name="id")
     * @GeneratedValue
     */
    private int $id;

 	/**
     * @Column(type="array", name="custom_fields")
     */
    private ?array $customFields;

    /**
     * @Column(type="text", name="description")
     */
    private ?string $description;

    /**
     * @Column(type="boolean", name="is_active")
     */
    private bool $isActive;

    /**
     * @Column(type="string", name="name", unique=true)
     */
    private string $name;

    /**
     * @OneToMany(targetEntity="InvoiceEntry", mappedBy="supply")
     */
    private $invoiceEntries;

    /**
     * @Column(type="float", name="price")
     */
    private float $price;

    /**
     * @ManyToOne(targetEntity="Supplier", cascade={"persist"},)
     * @JoinColumn(name="supplier_id", referencedColumnName="id")
     */
    private ?Supplier $supplier;

    /**
     * @ManyToOne(targetEntity="SupplyGroup", cascade={"persist"},)
     * @JoinColumn(name="supply_group_id", referencedColumnName="id")
     */
    private SupplyGroup $supplyGroup;

    /**
     * @Column(type="string", name="unit")
     */
    private string $unit;

    /**
     * @Column(type="float", name="vat_percentage")
     */
    private float $vatPercentage;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomField(string $field): ?string
    {
    	if (isset($this->customFields[$field])) {
    		return $this->customFields[$field];
    	}

    	return null;
    }

    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getInvoiceEntries()
    {
        return $this->invoiceEntries;
    }

    public function getIsActive(): bool
    {
    	return $this->isActive;
    }

    public function getMinimumPrice(): ?float
    {
        $minimumPrice = PHP_FLOAT_MAX;

        foreach($this->invoiceEntries as $invoiceEntry) {
            $minimumPrice = min($minimumPrice, $invoiceEntry->getPrice() / $invoiceEntry->getQuantity());
        }

        return $minimumPrice == PHP_FLOAT_MAX ? null : round($minimumPrice, 2);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
    	return $this->price;
    }

    public function getSupplier(): ?Supplier
    {
    	return $this->supplier;
    }

    public function getSupplyGroup(): SupplyGroup
    {
    	return $this->supplyGroup;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getVatPercentage(): float
    {
        return $this->vatPercentage;
    }

    public function setCustomFields(array $customFields): void
    {
    	$this->customFields = $customFields;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setIsActive(bool $isActive): void
    {
    	$this->isActive = $isActive;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setPrice(float $price): void
    {
    	$this->price = $price;
    }

    public function setSupplier(?Supplier $supplier): void
    {
    	$this->supplier = $supplier;
    }

    public function setSupplyGroup(SupplyGroup $supplyGroup): void
    {
    	$this->supplyGroup = $supplyGroup;
    }

    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }

    public function setVatPercentage(float $vatPercentage): void
    {
        $this->vatPercentage = $vatPercentage;
    }
}