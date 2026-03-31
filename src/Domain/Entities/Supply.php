<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Entities\SupplyGroup;
use Domain\Enums\PriceUnit;
use Domain\Repositories\SuppliesRepository;

#[ORM\Entity(repositoryClass: SuppliesRepository::class)]
#[ORM\Table(name: 'supplies')]
class Supply
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'id')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'json', name: 'custom_fields')]
    private $customFields;

    #[ORM\Column(type: 'string', name: 'name', unique: true)]
    private string $name;

    #[ORM\Column(type: 'float', name: 'price')]
    private float $price;

    #[ORM\Column(type: 'string', enumType: PriceUnit::class, name: 'price_unit')]
    private PriceUnit $priceUnit;

    #[ORM\Column(type: 'float', name: 'vat_rate', nullable: true)]
    private ?float $vatRate = null;

    #[ORM\ManyToOne(targetEntity: SupplyGroup::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'supply_group_id', referencedColumnName: 'id')]
    private SupplyGroup $supplyGroup;

    #[ORM\OneToMany(targetEntity: SupplyPriceHistory::class, mappedBy: 'supply')]
    private Collection $priceHistory;

    public function __construct()
    {
        $this->priceHistory = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomField(string $field)
    {
    	if (isset($this->customFields[$field])) {
    		return $this->customFields[$field];
    	} else {
    		return null;
    	}
    }

    public function getCustomFields()
    {
    	return $this->customFields;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getPriceUnit(): string
    {
        return $this->priceUnit->value;
    }

    public function getVatRate(): ?float
    {
        return $this->vatRate;
    }

    public function getSupplyGroup(): SupplyGroup
    {
    	return $this->supplyGroup;
    }

    public function setCustomField($field, $value): void
    {
        $this->customFields[$field] = $value;
    }
    
    public function setCustomFields($customFields): void
    {
    	$this->customFields = $customFields;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function setPriceUnit(PriceUnit $priceUnit): void
    {
        $this->priceUnit = $priceUnit;
    }

    public function setVatRate(?float $vatRate): void
    {
        $this->vatRate = $vatRate;
    }

    public function setSupplyGroup(SupplyGroup $supplyGroup): void
    {
    	$this->supplyGroup = $supplyGroup;
    }

    public function getPriceHistory(): Collection
    {
        return $this->priceHistory;
    }

    public function getPriceAt(DateTimeInterface $date): float
    {
        $matching = $this->priceHistory
            ->filter(fn(SupplyPriceHistory $h) => $h->getValidFrom() <= $date)
            ->toArray();

        if (empty($matching)) {
            return $this->price;
        }

        usort($matching, fn($a, $b) => $b->getValidFrom() <=> $a->getValidFrom());

        return $matching[0]->getPrice();
    }
}