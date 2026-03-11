<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\LanguagesRepository;

#[ORM\Entity(repositoryClass: LanguagesRepository::class)]
#[ORM\Table(name: 'languages')]
class Language
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'string', name: 'locale')]
    private string $locale;

    #[ORM\Column(type: 'string', name: 'name', unique: true)]
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