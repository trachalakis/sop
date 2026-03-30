<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Entities\Recipe;
use Domain\Entities\Supply;

#[ORM\Entity]
#[ORM\Table(name: 'ingredients')]
class Ingredient
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Recipe::class, inversedBy: 'ingredients')]
    #[ORM\JoinColumn(name: 'recipe_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Recipe $recipe;

    #[ORM\ManyToOne(targetEntity: Supply::class)]
    #[ORM\JoinColumn(name: 'supply_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Supply $supply = null;

    #[ORM\ManyToOne(targetEntity: Recipe::class)]
    #[ORM\JoinColumn(name: 'preparation_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Recipe $preparation = null;

    #[ORM\Column(type: 'float', name: 'quantity')]
    private float $quantity;

    #[ORM\Column(type: 'string', name: 'unit')]
    private string $unit;

    public function getId(): int
    {
        return $this->id;
    }

    public function getRecipe(): Recipe
    {
        return $this->recipe;
    }

    public function getSupply(): ?Supply
    {
        return $this->supply;
    }

    public function getPreparation(): ?Recipe
    {
        return $this->preparation;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setRecipe(Recipe $recipe): void
    {
        $this->recipe = $recipe;
    }

    public function setSupply(?Supply $supply): void
    {
        $this->supply = $supply;
    }

    public function setPreparation(?Recipe $preparation): void
    {
        $this->preparation = $preparation;
    }

    public function setQuantity(float $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }
}
