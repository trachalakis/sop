<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\MenuSection;
use Domain\Entities\Language;

/**
 * @Entity
 * @Table(name="menu_section_translations")
 **/
class MenuSectionTranslation
{
	/**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

	/**
     * @ManyToOne(targetEntity="Language")
     * @JoinColumn(name="language_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private Language $language;

	/**
     * @ManyToOne(targetEntity="MenuSection", inversedBy="translations")
     * @JoinColumn(name="menu_section_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private MenuSection $menuSection;

    /**
     * @Column(type="string", name="name")
     */
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