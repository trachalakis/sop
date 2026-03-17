<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\Supply;
use Domain\Repositories\SupplyGroupsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupplyGroupsRepository::class)]
#[ORM\Table(name: 'supply_groups')]
class SupplyGroup
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string', unique: true)]
    private string $name;

    #[ORM\OneToMany(targetEntity: Supply::class, mappedBy: 'supplyGroup', cascade: ['persist'])]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private $supplies;

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSupplies()
    {
    	return $this->supplies;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}