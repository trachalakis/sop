<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\TablesRepository;

#[ORM\Entity(repositoryClass: TablesRepository::class)]
#[ORM\Table(name: 'tables')]
class Table
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'string', unique: true)]
    private string $name;

    #[ORM\OneToMany(targetEntity: 'Order', mappedBy: 'table', cascade: ['persist'])]
    private $orders;

    public function getId()
    {
        return $this->id;
    }

	public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getName(): string
    {
        return $this->name;
    }

	public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }
}