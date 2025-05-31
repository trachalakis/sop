<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\MenuItem;
use Domain\Entities\Language;

/**
 * @Entity
 * @Table(name="menu_item_translations")
 **/
class MenuItemTranslation
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
     * @ManyToOne(targetEntity="MenuItem", inversedBy="translations")
     * @JoinColumn(name="menu_item_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private MenuItem $menuItem;

    /**
     * @Column(type="string", name="name")
     */
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