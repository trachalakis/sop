<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Domain\Entities\Menu;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\MenuSectionsRepository')]
#[ORM\Table(name: 'menu_sections')]
class MenuSection
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'boolean', name: 'is_public')]
    private bool $isPublic;

    #[ORM\ManyToOne(targetEntity: Menu::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'menu_id', referencedColumnName: 'id')]
    private Menu $menu;

    #[ORM\OneToMany(targetEntity: MenuItem::class, mappedBy: 'menuSection', cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private $menuItems;

    #[ORM\Column(type: 'integer', name: 'position')]
    private int $position;

    #[ORM\Column(type: 'integer', name: 'print_menu_page')]
    private int $printMenuPage;

    #[ORM\OneToMany(targetEntity: MenuSectionTranslation::class, mappedBy: 'menuSection', cascade: ['persist'], orphanRemoval: true)]
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->menuItems = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getActiveMenuItems()
    {
    	return $this->getMenuItems()->filter(function ($menuItem) {
    		return $menuItem->getIsActive();
    	});
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getIsPublic(): bool
    {
        return $this->isPublic;
    }

    public function getMenu(): Menu
    {
        return $this->menu;
    }

    public function setMenu(Menu $menu): void
    {
        $this->menu = $menu;
    }

    public function getMenuItems()
    {
        return $this->menuItems;
    }

	public function getPosition(): int
    {
        return $this->position;
    }

    public function getPrintMenuPage(): int
    {
    	return $this->printMenuPage;
    }

    public function getPublicMenuItems()
    {
    	return $this->getActiveMenuItems()->filter(function ($menuItem) {
    		return $menuItem->getIsPublic();
    	});
    }

    public function getTranslation(string $language): ?MenuSectionTranslation
    {
    	foreach($this->getTranslations() as $translation) {
    		if ($translation->getLanguage()->getIsoCode() == $language) {
    			return $translation;
    		}
    	}

    	return null;
    }

    public function getTranslations()
    {
    	return $this->translations;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setIsPublic(bool $isPublic): void
    {
        $this->isPublic = $isPublic;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function setPrintMenuPage(int $printMenuPage): void
    {
    	$this->printMenuPage = $printMenuPage;
    }

    public function setTranslations($translations): void
    {
    	$this->translations = $translations;
    }
}