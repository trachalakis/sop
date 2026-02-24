<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\MenuItem;
use Domain\Entities\Ingredient;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\RecipesRepository')]
#[ORM\Table(name: 'recipes')]
class Recipe
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'id')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'text', name: 'comments')]
    private ?string $comments;

    #[ORM\Column(type: 'integer', name: 'duration')]
    private ?int $duration;

    #[ORM\OneToMany(targetEntity: Ingredient::class, mappedBy: 'recipe', cascade: ['persist'], orphanRemoval: true)]
    private $ingredients;

    #[ORM\OneToOne(targetEntity: MenuItem::class)]
    #[ORM\JoinColumn(name: 'menu_item_id', referencedColumnName: 'id')]
    private ?MenuItem $menuItem;

    #[ORM\Column(type: 'string', name: 'name')]
    private ?string $name;

    #[ORM\Column(type: 'float', name: 'yield')]
    private float $yield;

    #[ORM\Column(type: 'string', name: 'yield_unit')]
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