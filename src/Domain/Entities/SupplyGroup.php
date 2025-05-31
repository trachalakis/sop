<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\Supply;

/**
 * @Entity(repositoryClass="Domain\Repositories\SupplyGroupsRepository")
 * @Table(name="supply_groups")
 **/
class SupplyGroup
{
	/**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="string", unique=true)
     */
    private string $name;

    /**
     * @Column(type="integer", name="position")
     */
    private int $position;

    /**
     * @Column(type="boolean", name="show_in_shopping_list")
     */
    private string $showInShoppingList;

    /**
     * @OneToMany(targetEntity="Supply", mappedBy="supplyGroup", cascade={"persist"})
     * @OrderBy({"isActive" = "DESC", "name" = "ASC"})
     */
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