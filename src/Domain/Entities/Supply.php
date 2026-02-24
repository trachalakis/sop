<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\Supplier;
use Domain\Entities\SupplyGroup;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\SuppliesRepository')]
#[ORM\Table(name: 'supplies')]
class Supply
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'id')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'array', name: 'custom_fields')]
    private ?array $customFields;

    #[ORM\Column(type: 'text', name: 'description')]
    private ?string $description;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'string', name: 'name', unique: true)]
    private string $name;

    #[ORM\OneToMany(targetEntity: InvoiceEntry::class, mappedBy: 'supply')]
    private $invoiceEntries;

    #[ORM\Column(type: 'float', name: 'price')]
    private float $price;

    #[ORM\ManyToOne(targetEntity: Supplier::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'supplier_id', referencedColumnName: 'id')]
    private ?Supplier $supplier;

    #[ORM\ManyToOne(targetEntity: SupplyGroup::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'supply_group_id', referencedColumnName: 'id')]
    private SupplyGroup $supplyGroup;

    #[ORM\Column(type: 'string', name: 'unit')]
    private string $unit;

    #[ORM\Column(type: 'float', name: 'vat_percentage')]
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