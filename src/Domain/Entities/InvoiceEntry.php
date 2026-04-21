<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\InvoiceEntriesRepository;

#[ORM\Entity(repositoryClass: InvoiceEntriesRepository::class)]
#[ORM\Table(name: 'invoice_entries')]
class InvoiceEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Invoice $invoice;

    #[ORM\Column(type: 'string', length: 500)]
    private string $description;

    #[ORM\Column(type: 'float')]
    private float $quantity;

    #[ORM\Column(type: 'float', name: 'unit_price')]
    private float $unitPrice;

    #[ORM\Column(type: 'float', name: 'effective_unit_price', nullable: true)]
    private ?float $effectiveUnitPrice = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $extras = null;

    #[ORM\ManyToOne(targetEntity: SupplyAlias::class)]
    #[ORM\JoinColumn(name: 'supply_alias_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?SupplyAlias $supplyAlias = null;

    public function getId(): int { return $this->id; }
    public function getInvoice(): Invoice { return $this->invoice; }
    public function getDescription(): string { return $this->description; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getEffectiveUnitPrice(): ?float { return $this->effectiveUnitPrice; }
    public function getExtras(): ?array { return $this->extras; }
    public function getSupplyAlias(): ?SupplyAlias { return $this->supplyAlias; }

    public function setInvoice(Invoice $invoice): void { $this->invoice = $invoice; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setQuantity(float $quantity): void { $this->quantity = $quantity; }
    public function setUnitPrice(float $unitPrice): void { $this->unitPrice = $unitPrice; }
    public function setEffectiveUnitPrice(?float $effectiveUnitPrice): void { $this->effectiveUnitPrice = $effectiveUnitPrice; }
    public function setExtras(?array $extras): void { $this->extras = $extras; }
    public function setSupplyAlias(?SupplyAlias $supplyAlias): void { $this->supplyAlias = $supplyAlias; }
}
