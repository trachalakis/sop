<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\SupplyGroup;
use Domain\Repositories\SuppliesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuppliesRepository::class)]
#[ORM\Table(name: 'supplies')]
class Supply
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'id')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'json', name: 'custom_fields')]
    private $customFields;

    #[ORM\Column(type: 'string', name: 'name', unique: true)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: SupplyGroup::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'supply_group_id', referencedColumnName: 'id')]
    private SupplyGroup $supplyGroup;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomField(string $field)
    {
    	if (isset($this->customFields[$field])) {
    		return $this->customFields[$field];
    	} else {
    		return null;
    	}
    }

    public function getCustomFields()
    {
    	return $this->customFields;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSupplyGroup(): SupplyGroup
    {
    	return $this->supplyGroup;
    }

    public function setCustomField($field, $value): void
    {
        $this->customFields[$field] = $value;
    }
    
    public function setCustomFields($customFields): void
    {
    	$this->customFields = $customFields;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setSupplyGroup(SupplyGroup $supplyGroup): void
    {
    	$this->supplyGroup = $supplyGroup;
    }
}