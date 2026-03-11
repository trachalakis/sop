<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\UserPermissionsRepository;

#[ORM\Entity(repositoryClass: UserPermissionsRepository::class)]
#[ORM\Table(name: 'user_permissions')]
class UserPermission
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'simple_array', name: 'allowed_roles')]
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