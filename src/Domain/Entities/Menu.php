<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTimeImmutable;
use Domain\Enums\MenuType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\MenusRepository')]
#[ORM\Table(name: 'menus')]
class Menu
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'string', enumType: MenuType::class, name: 'menu_type')]
    private MenuType $menuType;

    #[ORM\OneToMany(targetEntity: MenuSection::class, mappedBy: 'menu', cascade: ['persist'])]
    #[ORM\OrderBy(['isActive' => 'DESC', 'position' => 'ASC'])]
    private $menuSections;

    #[ORM\Column(type: 'string', name: 'name')]
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

    public function getMenuType(): string
    {
        return $this->menuType->value;
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

    public function setMenuType(MenuType $menuType): void
    {
        $this->menuType = $menuType;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}