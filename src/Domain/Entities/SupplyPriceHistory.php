<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\SupplyPriceHistoryRepository;

#[ORM\Entity(repositoryClass: SupplyPriceHistoryRepository::class)]
#[ORM\Table(name: 'supply_price_history')]
class SupplyPriceHistory
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Supply::class, inversedBy: 'priceHistory')]
    #[ORM\JoinColumn(name: 'supply_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Supply $supply;

    #[ORM\Column(type: 'float')]
    private float $price;

    #[ORM\Column(type: 'datetimetz_immutable', name: 'valid_from')]
    private DateTimeInterface $validFrom;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSupply(): Supply
    {
        return $this->supply;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getValidFrom(): DateTimeInterface
    {
        return $this->validFrom;
    }

    public function setSupply(Supply $supply): void
    {
        $this->supply = $supply;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function setValidFrom(DateTimeInterface $validFrom): void
    {
        $this->validFrom = $validFrom;
    }
}
