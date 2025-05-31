<?php

declare(strict_types=1);

namespace Domain\Entities;

/**
 * @Entity(repositoryClass="Domain\Repositories\UserPermissionsRepository")
 * @Table(name="user_permissions")
 **/
class UserPermission
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="array", name="allowed_roles")
     */
    private array $allowedRoles;

    /**
     * @Column(type="string", name="path")
     */
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