<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\UserPermissionsRepository')]
#[ORM\Table(name: 'user_permissions')]
class UserPermission
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'array', name: 'allowed_roles')]
    private array $allowedRoles;

    #[ORM\Column(type: 'string', name: 'path')]
    private string $path;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAllowedRoles(): array
    {
        return $this->allowedRoles;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setAllowedRoles(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function setPath(string $path)
    {
        $this->path = $path;
    }
}