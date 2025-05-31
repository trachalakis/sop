<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\Supply;
use Domain\Entities\Recipe;

/**
 * @Entity
 * @Table(name="ingredients")
 **/
class Ingredient
{
	/**
     * @Id
     * @Column(type="integer", name="id")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="float", name="quantity")
     */
    private float $quantity;

    /**
     * @ManyToOne(targetEntity="Recipe", inversedBy="ingredients", cascade={"persist"})
     * @JoinColumn(name="recipe_id", referencedColumnName="id")
     */
    private Recipe $recipe;

    /**
     * @ManyToOne(targetEntity="Recipe")
     * @JoinColumn(name="preparation_id", referencedColumnName="id")
     */
    private ?Recipe $preparation;

    /**
     * @ManyToOne(targetEntity="Supply")
     * @JoinColumn(name="supply_id", referencedColumnName="id")
     */
    private ?Supply $supply;

    /**
     * @Column(type="string", name="unit")
     */
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