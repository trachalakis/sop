<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Entities\Extra;
use Domain\Entities\MenuSection;
use Domain\Entities\Printer;
use Domain\Enums\PriceUnit;
use Domain\Repositories\MenuItemsRepository;

#[ORM\Entity(repositoryClass: MenuItemsRepository::class)]
#[ORM\Table(name: 'menu_items')]
class MenuItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'integer', name: 'available_quantity')]
    private ?int $availableQuantity;

    #[ORM\Column(type: 'json', name: 'custom_fields')]
    private ?array $customFields;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'boolean', name: 'is_archived')]
    private bool $isArchived;

    #[ORM\Column(type: 'boolean', name: 'is_drink')]
    private bool $isDrink;

    #[ORM\Column(type: 'string', enumType: PriceUnit::class, name: 'price_unit')]
    private PriceUnit $priceUnit;

    #[ORM\ManyToOne(targetEntity: MenuSection::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'menu_section_id', referencedColumnName: 'id')]
    private MenuSection $menuSection;

    #[ORM\OneToMany(targetEntity: Extra::class, mappedBy: 'menuItem', cascade: ['persist'], orphanRemoval: true)]
    private Collection $extras;

    #[ORM\Column(type: 'integer', name: 'position')]
    private int $position;

    #[ORM\Column(type: 'float', name: 'price')]
    private float $price;

    #[ORM\ManyToMany(targetEntity: Printer::class)]
    #[ORM\JoinTable(name: 'menu_items_printers', joinColumns: [new ORM\JoinColumn(name: 'menu_item_id', referencedColumnName: 'id')], inverseJoinColumns: [new ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id')])]
    private $printers;

    #[ORM\Column(type: 'boolean', name: 'track_available_quantity')]
    private bool $trackAvailableQuantity;

    #[ORM\OneToMany(targetEntity: MenuItemTranslation::class, mappedBy: 'menuItem', cascade: ['persist'], orphanRemoval: true)]
    private $translations;

    public function __construct() {
        $this->extras = new ArrayCollection;
        $this->printers = new ArrayCollection;
        $this->translations = new ArrayCollection;
    }

    public function addExtra(Extra $extra)
    {
        $this->extras[] = $extra;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAllExtras()
    {
        $extras = [];

        foreach ($this->getMenuSection()->getExtras() as $extra) {
            $extras[] = $extra;
        }
        foreach ($this->getExtras() as $extra) {
            $extras[] = $extra;
        }

        return $extras;
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

    public function getExtras()
    {
        return $this->extras;
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

    public function getMenuPosition(): int
    {
        return ($this->getMenuSection()->getPosition() * 100) + $this->getPosition();
    }

    public function getMenuSection(): MenuSection
    {
        return $this->menuSection;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getPriceUnit(): string
    {
        return $this->priceUnit->value;
    }

    public function getPrinters()
    {
        return $this->printers;
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

    public function setExtras($extras): void //TODO add arg type
    {
        $this->extras = $extras;
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

    public function setPriceUnit(PriceUnit $priceUnit): void
    {
        $this->priceUnit = $priceUnit;
    }

    public function setPrinters($printers): void
    {
        $this->printers = $printers;
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