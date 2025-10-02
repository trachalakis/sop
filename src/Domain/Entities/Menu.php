<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity(repositoryClass="Domain\Repositories\MenusRepository")
 * @Table(name="menus")
 **/
class Menu
{
	/**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="datetime_immutable", name="created_at")
     */
    private DateTimeImmutable $createdAt;

    /**
     * @Column(type="boolean", name="is_active")
     */
    private bool $isActive;

    /**
     * @OneToMany(targetEntity="MenuSection", mappedBy="menu", cascade={"persist"})
     * @OrderBy({"isActive" = "DESC", "position" = "ASC"})
     */
    private $menuSections;

    /**
     * @Column(type="string", name="name")
     */
    private string $name;

    public function __construct()
    {
        $this->menuSections = new ArrayCollection;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getMenuSections()
    {
        return $this->menuSections;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}