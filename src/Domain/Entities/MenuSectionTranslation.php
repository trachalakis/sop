<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\MenuSection;
use Domain\Entities\Language;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'menu_section_translations')]
class MenuSectionTranslation
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(name: 'language_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Language $language;

    #[ORM\ManyToOne(targetEntity: MenuSection::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'menu_section_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private MenuSection $menuSection;

    #[ORM\Column(type: 'string', name: 'name')]
    private string $name;

    public function getLanguage(): Language
    {
    	return $this->language;
    }

    public function getMenuSection(): MenuSection
    {
    	return $this->menuSection;
    }

    public function getName(): string
    {
    	return $this->name;
    }

    public function setLanguage(Language $language): void
    {
    	$this->language = $language;
    }

    public function setMenuSection(MenuSection $menuSection): void
    {
    	$this->menuSection = $menuSection;
    }

    public function setName(string $name): void
    {
    	$this->name = $name;
    }
}