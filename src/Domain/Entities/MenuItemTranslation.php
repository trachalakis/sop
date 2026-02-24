<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\MenuItem;
use Domain\Entities\Language;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'menu_item_translations')]
class MenuItemTranslation
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(name: 'language_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Language $language;

    #[ORM\ManyToOne(targetEntity: MenuItem::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'menu_item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private MenuItem $menuItem;

    #[ORM\Column(type: 'string', name: 'name')]
    private string $name;

    public function getLanguage(): Language
    {
    	return $this->language;
    }

    public function getMenuItem(): MenuItem
    {
    	return $this->menuItem;
    }

    public function getName(): string
    {
    	return $this->name;
    }

    public function setLanguage(Language $language): void
    {
    	$this->language = $language;
    }

    public function setMenuItem(MenuItem $menuItem): void
    {
    	$this->menuItem = $menuItem;
    }    

    public function setName(string $name): void
    {
    	$this->name = $name;
    }
}