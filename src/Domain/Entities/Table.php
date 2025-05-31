<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\Order;

/**
 * @Entity(repositoryClass="Domain\Repositories\TablesRepository")
 * @Table(name="tables")
 **/
class Table
{
	/**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="boolean", name="is_active")
     */
    private bool $isActive;

    /**
     * @Column(type="string", unique=true)
     */
    private string $name;

    /**
     * @OneToMany(targetEntity="Order", mappedBy="table", cascade={"persist"})
     */
    private $orders;

    public function getId()
    {
        return $this->id;
    }

	public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getName(): string
    {
        return $this->name;
    }

	public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }
}