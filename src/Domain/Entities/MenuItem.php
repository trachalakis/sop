<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Domain\Entities\MenuSection;
use Domain\Entities\MenuItemExtra;
use Domain\Entities\MenuItemPrice;
use Domain\Entities\Supply;
use Domain\Entities\Station;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\MenuItemsRepository')]
#[ORM\Table(name: 'menu_items')]
class MenuItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'integer', name: 'available_quantity')]
    private ?int $availableQuantity;

    #[ORM\Column(type: 'simple_array', name: 'custom_fields')]
    private ?array $customFields;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'boolean', name: 'is_archived')]
    private bool $isArchived;

    #[ORM\Column(type: 'boolean', name: 'is_drink')]
    private bool $isDrink;

    #[ORM\Column(type: 'boolean', name: 'is_price_per_kg')]
    private bool $isPricePerKg;

    #[ORM\Column(type: 'boolean', name: 'is_public')]
    private bool $isPublic;

    #[ORM\ManyToOne(targetEntity: MenuSection::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'menu_section_id', referencedColumnName: 'id')]
    private MenuSection $menuSection;

    #[ORM\OneToMany(targetEntity: MenuItemExtra::class, mappedBy: 'menuItem', cascade: ['persist'], orphanRemoval: true)]
    private Collection $menuItemExtras;

    #[ORM\Column(type: 'integer', name: 'position')]
    private int $position;

    #[ORM\Column(type: 'float', name: 'price')]
    private float $price;

    #[ORM\ManyToMany(targetEntity: Station::class)]
    #[ORM\JoinTable(name: 'menu_items_stations', joinColumns: [new ORM\JoinColumn(name: 'menu_item_id', referencedColumnName: 'id')], inverseJoinColumns: [new ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id')])]
    private $stations;

    #[ORM\Column(type: 'boolean', name: 'track_available_quantity')]
    private bool $trackAvailableQuantity;

    #[ORM\OneToMany(targetEntity: MenuItemTranslation::class, mappedBy: 'menuItem', cascade: ['persist'], orphanRemoval: true)]
    private $translations;

    public function __construct() {
        $this->stations = new ArrayCollection;
        $this->menuItemExtras = new ArrayCollection;
        $this->translations = new ArrayCollection;
    }

    public function addMenuItemExtra(MenuItemExtra $menuItemExtra)
    {
        $this->menuItemExtras[] = $menuItemExtra;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAvailableQuantity(): ?int
    {
        return $this->availableQuantity;
    }

    public function getCustomField(string $field): ?string
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

    public function getIsArchived(): bool
    {
        return $this->isArchived;
    }

    public function getIsDrink(): bool
    {
    	return $this->isDrink;
    }

    public function getIsPricePerKg(): bool
    {
    	return $this->isPricePerKg;
    }

    public function getIsPublic(): bool
    {
        return $this->isPublic;
    }

    public function getMenuPosition(): int
    {
        return ($this->getMenuSection()->getPosition() * 100) + $this->getPosition();
    }

    public function getMenuSection(): MenuSection
    {
        return $this->menuSection;
    }

    public function getMenuItemExtras()
    {
        return $this->menuItemExtras;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getStations()
    {
        return $this->stations;
    }

    public function getTrackAvailableQuantity(): bool
    {
        return $this->trackAvailableQuantity;
    }

    public function getTranslation(string $language): ?MenuItemTranslation
    {
    	foreach($this->translations as $translation) {
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

    public function setAvailableQuantity(?int $availableQuantity): void
    {
        $this->availableQuantity = $availableQuantity;
    }

    public function setCustomFields(?array $customFields): void
    {
    	$this->customFields = $customFields;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setIsArchived(bool $isArchived): void
    {
        $this->isArchived = $isArchived;
    }

    public function setIsDrink(bool $isDrink): void
    {
    	$this->isDrink = $isDrink;
    }

    public function setIsPricePerKg(bool $isPricePerKg): void
    {
   		$this->isPricePerKg = $isPricePerKg;
    }

    public function setIsPublic(bool $isPublic): void
    {
        $this->isPublic = $isPublic;
    }

    public function setMenuItemExtras($menuItemExtras): void //TODO add arg type
    {
        $this->menuItemExtras = $menuItemExtras;
    }

    public function setMenuSection(MenuSection $menuSection): void
    {
        $this->menuSection = $menuSection;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function setStations($stations): void
    {
        $this->stations = $stations;
    }

    public function setTrackAvailableQuantity(bool $trackAvailableQuantity): void
    {
        $this->trackAvailableQuantity = $trackAvailableQuantity;
    }

    public function setTranslations($translations): void
    {
    	$this->translations = $translations;
    }
}