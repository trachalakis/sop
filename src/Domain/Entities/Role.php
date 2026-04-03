<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\RolesRepository;

#[ORM\Entity(repositoryClass: RolesRepository::class)]
#[ORM\Table(name: 'roles')]
class Role
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string', name: 'name', unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', name: 'label')]
    private string $label;

    #[ORM\Column(type: 'float', name: 'minimum_man_hours', nullable: true)]
    private ?float $minimumManHours;

    public function __construct(string $name, string $label, ?float $minimumManHours = null)
    {
        $this->setName($name);
        $this->setLabel($label);
        $this->setMinimumManHours($minimumManHours);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getMinimumManHours(): ?float
    {
        return $this->minimumManHours;
    }

    public function setMinimumManHours(?float $minimumManHours): void
    {
        $this->minimumManHours = $minimumManHours;
    }
}
