<?php

declare(strict_types=1);

namespace Domain\Entities;

/**
 * @Entity(repositoryClass="Domain\Repositories\LanguagesRepository")
 * @Table(name="languages")
 **/
class Language
{
	/**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="boolean", name="is_active")
     */
    private bool $isActive;

    /**
     * @Column(type="string", name="locale")
     */
    private string $locale;

    /**
     * @Column(type="string", name="name", unique=true)
     */
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getIsActive(): bool
    {
    	return $this->isActive;
    }

    public function getIsoCode(): string
    {
    	return substr($this->locale, 0, 2);
    }

    public function getLocale(): string
    {
    	return $this->locale;
    }

    public function getName(): string
    {
    	return $this->name;
    }

    public function setIsActive(bool $isActive): void
    {
    	$this->isActive = $isActive;
    }

    public function setLocale(string $locale): void
    {
    	$this->locale = $locale;
    }

    public function setName(string $name): void
    {
    	$this->name = $name;
    }
}