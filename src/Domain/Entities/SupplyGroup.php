<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\Supply;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\SupplyGroupsRepository')]
#[ORM\Table(name: 'supply_groups')]
class SupplyGroup
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string', unique: true)]
    private string $name;

    #[ORM\Column(type: 'integer', name: 'position')]
    private int $position;

    #[ORM\Column(type: 'boolean', name: 'show_in_shopping_list')]
    private string $showInShoppingList;

    #[ORM\OneToMany(targetEntity: Supply::class, mappedBy: 'supplyGroup', cascade: ['persist'])]
    #[ORM\OrderBy(['isActive' => 'DESC', 'name' => 'ASC'])]
    private $supplies;

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShowInShoppingList()
    {
        return $this->showInShoppingList;
    }

    public function getSupplies()
    {
    	return $this->supplies;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setShowInShoppingList(bool $showInShoppingList): void
    {
        $this->showInShoppingList = $showInShoppingList;
    }
}