<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Entities\Extra;
use Domain\Entities\Menu;
use Domain\Repositories\MenuSectionsRepository;

#[ORM\Entity(repositoryClass: MenuSectionsRepository::class)]
#[ORM\Table(name: 'menu_sections')]
class MenuSection
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'json', name: 'custom_fields')]
    private ?array $customFields;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'boolean', name: 'is_public')]
    private bool $isPublic;

    #[ORM\ManyToOne(targetEntity: Menu::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'menu_id', referencedColumnName: 'id')]
    private Menu $menu;

    #[ORM\OneToMany(targetEntity: MenuItem::class, mappedBy: 'menuSection', cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $menuItems;

    #[ORM\OneToMany(targetEntity: Extra::class, mappedBy: 'menuSection', cascade: ['persist'], orphanRemoval: true)]
    private Collection $extras;

    #[ORM\Column(type: 'integer', name: 'position')]
    private int $position;

    #[ORM\Column(type: 'integer', name: 'print_menu_page')]
    private int $printMenuPage;

    #[ORM\OneToMany(targetEntity: MenuSectionTranslation::class, mappedBy: 'menuSection', cascade: ['persist'], orphanRemoval: true)]
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->menuItems = new ArrayCollection();
        $this->extras = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function addExtra(Extra $extra)
    {
        $this->extras[] = $extra;
    }

    public function getActiveMenuItems()
    {
    	return $this->getMenuItems()->filter(function ($menuItem) {
    		return $menuItem->getIsActive();
    	});
    }

    public function getCustomField(string $field)
    {
    	if (isset($this->customFields[$field])) {
    		return $this->customFields[$field];
    	} else {
    		return null;
    	}
    }

    public function getCustomFields(): ?array
    {
    	return $this->customFields;
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

    public function getMenuItems(): Collection
    {
        return $this->menuItems;
    }

    public function getExtras(): Collection
    {
        return $this->extras;
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

    public function getTranslations(): Collection
    {
    	return $this->translations;
    }
    
    public function setCustomField($field, $value): void
    {
        $this->customFields[$field] = $value;
    }
    
    public function setCustomFields(?array $customFields): void
    {
    	$this->customFields = $customFields;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setIsPublic(bool $isPublic): void
    {
        $this->isPublic = $isPublic;
    }

    public function setExtras($extras): void //TODO add arg type
    {
        $this->extras = $extras;
    }

    public function setMenuItems(Collection $menuItems): void
    {
        $this->menuItems = $menuItems;
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