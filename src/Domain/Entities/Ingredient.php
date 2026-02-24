<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\Supply;
use Domain\Entities\Recipe;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ingredients')]
class Ingredient
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'id')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'float', name: 'quantity')]
    private float $quantity;

    #[ORM\ManyToOne(targetEntity: Recipe::class, inversedBy: 'ingredients', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'recipe_id', referencedColumnName: 'id')]
    private Recipe $recipe;

    #[ORM\ManyToOne(targetEntity: Recipe::class)]
    #[ORM\JoinColumn(name: 'preparation_id', referencedColumnName: 'id')]
    private ?Recipe $preparation;

    #[ORM\ManyToOne(targetEntity: Supply::class)]
    #[ORM\JoinColumn(name: 'supply_id', referencedColumnName: 'id')]
    private ?Supply $supply;

    #[ORM\Column(type: 'string', name: 'unit')]
    private string $unit;

    public function getId(): int
    {
    	return $this->id;
    }

     public function getPreparation(): ?Recipe
    {
    	return $this->preparation;
    }

    public function getQuantity(): float
    {
    	return $this->quantity;
    }

   public function getRecipe(): Recipe
    {
    	return $this->recipe;
    }

    public function getSupply(): ?Supply
    {
    	return $this->supply;
    }

    public function getUnit(): string
    {
    	return $this->unit;
    }

    public function setPreparation(?Recipe $preparation)
    {
    	$this->preparation = $preparation;
    }

    public function setQuantity(float $quantity): void
    {
    	$this->quantity = $quantity;
    }

    public function setRecipe(Recipe $recipe): void
    {
    	$this->recipe = $recipe;
    }

    public function setSupply(?Supply $supply): void
    {
    	$this->supply = $supply;
    }

    public function setUnit(string $unit): void
    {
    	$this->unit = $unit;
    }
}