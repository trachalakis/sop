<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Entities\Ingredient;
use Domain\Entities\MenuItem;
use Domain\Repositories\RecipesRepository;

#[ORM\Entity(repositoryClass: RecipesRepository::class)]
#[ORM\Table(name: 'recipes')]
class Recipe
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string', name: 'name', nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer', name: 'duration')]
    private int $duration = 0;

    #[ORM\Column(type: 'float', name: 'yield')]
    private float $yield = 0;

    #[ORM\Column(type: 'string', name: 'yield_unit')]
    private string $yieldUnit = 'item';

    #[ORM\Column(type: 'text', name: 'comments', nullable: true)]
    private ?string $comments = null;

    #[ORM\ManyToOne(targetEntity: MenuItem::class)]
    #[ORM\JoinColumn(name: 'menu_item_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?MenuItem $menuItem = null;

    #[ORM\OneToMany(targetEntity: Ingredient::class, mappedBy: 'recipe', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $ingredients;

    public function __construct()
    {
        $this->ingredients = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getYield(): float
    {
        return $this->yield;
    }

    public function getYieldUnit(): string
    {
        return $this->yieldUnit;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function getMenuItem(): ?MenuItem
    {
        return $this->menuItem;
    }

    public function getIngredients(): Collection
    {
        return $this->ingredients;
    }

    public function getSupplies(): Collection
    {
        return $this->ingredients->filter(fn(Ingredient $i) => $i->getSupply() !== null);
    }

    public function getPreparations(): Collection
    {
        return $this->ingredients->filter(fn(Ingredient $i) => $i->getPreparation() !== null);
    }

    public function getFoodCost(?\DateTimeInterface $date = null): float
    {
        $cost = 0.0;
        foreach ($this->getSupplies() as $ingredient) {
            $price = $date !== null
                ? $ingredient->getSupply()->getPriceAt($date)
                : $ingredient->getSupply()->getPrice();
            $cost += $ingredient->getQuantity() * $price;
        }
        foreach ($this->getPreparations() as $ingredient) {
            $prep = $ingredient->getPreparation();
            if ($prep->getYield() > 0) {
                $cost += $ingredient->getQuantity() * ($prep->getFoodCost($date) / $prep->getYield());
            }
        }
        return $cost;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    public function setYield(float $yield): void
    {
        $this->yield = $yield;
    }

    public function setYieldUnit(string $yieldUnit): void
    {
        $this->yieldUnit = $yieldUnit;
    }

    public function setComments(?string $comments): void
    {
        $this->comments = $comments;
    }

    public function setMenuItem(?MenuItem $menuItem): void
    {
        $this->menuItem = $menuItem;
    }

    public function setIngredients(array $ingredients): void
    {
        $this->ingredients->clear();
        foreach ($ingredients as $ingredient) {
            $this->ingredients->add($ingredient);
        }
    }
}
