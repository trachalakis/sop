<?php

declare(strict_types=1);

namespace Domain\Entities;

/**
 * @Entity(repositoryClass="Domain\Repositories\PoStringsRepository")
 * @Table(name="po_strings")
 **/
class PoString
{
	/**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private int $id;

    /**
     * @Column(type="boolean", name="is_active")
     */
    private bool $isActive;

    /**
     * @Column(type="string", name="label")
     */
    private string $label;

    public function getId(): int
    {
        return $this->id;
    }

    public function getIsActive(): bool
    {
    	return $this->isActive;
    }

    public function getLabel(): string
    {
    	return $this->label;
    }

    public function getTranslation(string $language): ?PoStringTranslation
    {
    	foreach($this->translations as $translation) {
    		if ($translation->getLanguage()->getIsoCode() == $language) {
    			return $translation;
    		}
    	}

    	return null;
    }

    public function getTranslations() //TODO add type
    {
    	return $this->translations;
    }

    public function setIsActive(bool $isActive): void
    {
    	$this->isActive = $isActive;
    }

    public function setLabel(string $label): void
    {
    	$this->label = $label;
    }
}