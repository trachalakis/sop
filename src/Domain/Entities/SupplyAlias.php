<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\SupplyAliasesRepository;

#[ORM\Entity(repositoryClass: SupplyAliasesRepository::class)]
#[ORM\Table(name: 'supply_aliases')]
#[ORM\UniqueConstraint(columns: ['supplier_id', 'description'])]
class SupplyAlias
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Supply::class)]
    #[ORM\JoinColumn(name: 'supply_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Supply $supply;

    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(name: 'supplier_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Supplier $supplier;

    #[ORM\Column(type: 'string', length: 500)]
    private string $description;

    public function getId(): int { return $this->id; }
    public function getSupply(): Supply { return $this->supply; }
    public function getSupplier(): Supplier { return $this->supplier; }
    public function getDescription(): string { return $this->description; }

    public function setSupply(Supply $supply): void { $this->supply = $supply; }
    public function setSupplier(Supplier $supplier): void { $this->supplier = $supplier; }
    public function setDescription(string $description): void { $this->description = $description; }
}
