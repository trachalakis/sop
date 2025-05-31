<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\MenuItem;

/**
 * @Entity(repositoryClass="Domain\Repositories\RecipesRepository")
 * @Table(name="recipes")
 **/
class Recipe
{
	/**
     * @Id
     * @Column(type="integer", name="id")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="text", name="comments")
     */
    private ?string $comments;

    /**
     * @Column(type="integer", name="duration")
     */
    private ?int $duration;

    /**
     * @OneToMany(targetEntity="Ingredient", mappedBy="recipe", cascade={"persist"}, orphanRemoval=true)
     */
    private $ingredients;

    /**
     * @OneToOne(targetEntity="MenuItem")
     * @JoinColumn(name="menu_item_id", referencedColumnName="id")
     */
    private ?MenuItem $menuItem;

    /**
     * @Column(type="string", name="name")
     */
    private ?string $name;

    /**
     * @Column(type="float", name="yield")
     */
    private float $yield;

    /**
     * @Column(type="string", name="yield_unit")
     */
    private string $yieldUnit;

    public function getId(): int
    {
    	return $this->id;
    }

    public function getComments(): ?string
    {
    	return $this->comments;
    }

    public function getDuration(): ?int
    {
    	return $this->duration;
    }

    public function getIngredients()
    {
    	return $this->ingredients;
    }

    public function getMenuItem(): ?MenuItem
    {
    	return $this->menuItem;
    }

    public function getName(): ?string
    {
    	return $this->name;
    }

    public function getPreparations()
    {
    	return $this->getIngredients()->filter(function ($ingredient) {
    		return $ingredient->getSupply() == null;
     	});
    }

    public function getSupplies()
    {
    	return $this->getIngredients()->filter(function ($ingredient) {
    		return $ingredient->getSupply() != null;
     	});
    }

    public function getYield(): float
    {
        return $this->yield;
    }

    public function getYieldUnit(): string
    {
        return $this->yieldUnit;
    }

    public function setComments(?string $comments): void
    {
    	$this->comments = $comments;
    }

    public function setDuration(?int $duration): void
    {
    	$this->duration = $duration;
    }

    public function setIngredients($ingredients): void
    {
    	$this->ingredients = $ingredients;
    }

    public function setMenuItem(?MenuItem $menuItem): void
    {
    	$this->menuItem = $menuItem;
    }

    public function setName(?string $name): void
    {
    	$this->name = $name;
    }

    public function setYield(float $yield): void
    {
        $this->yield = $yield;
    }

    public function setYieldUnit(string $yieldUnit): void
    {
        $this->yieldUnit = $yieldUnit;
    }
}