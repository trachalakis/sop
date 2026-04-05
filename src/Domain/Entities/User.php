<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\UsersRepository;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string', name: 'email_address')]
    private string $emailAddress;

    #[ORM\Column(type: 'string', name: 'full_name')]
    private string $fullName;

    #[ORM\Column(type: 'float', name: 'hourly_rate')]
    private ?float $hourlyRate;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'string', name: 'notes')]
    private ?string $notes;

    #[ORM\Column(type: 'string', name: 'password_hash')]
    private string $passwordHash;

    #[ORM\ManyToMany(targetEntity: Role::class)]
    #[ORM\JoinTable(name: 'user_roles')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(onDelete: 'CASCADE')]
    private Collection $roles;

    public function __construct(
        bool $isActive,
        string $emailAddress,
        string $password,
        string $fullName,
        float $hourlyRate,
        array $roles
    ) {
        $this->roles = new ArrayCollection();
        $this->setIsActive($isActive);
        $this->setEmailAddress($emailAddress);
        $this->setPassword($password);
        $this->setFullName($fullName);
        $this->setHourlyRate($hourlyRate);
        $this->setRoles($roles);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getHourlyRate(): ?float
    {
        return $this->hourlyRate;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRoles(): array
    {
        return $this->roles->toArray();
    }

    public function hasRole(string $role): bool
    {
        foreach ($this->roles as $r) {
            if ($r->getName() === $role || $r->getName() === 'webmaster') {
                return true;
            }
        }
        return false;
    }

    public function isEmployee(): bool
    {
        foreach ($this->roles as $r) {
            if ($r->getName() === 'employee') {
                return true;
            }
        }
        return false;
    }

    public function isWaiter(): bool
    {
        foreach ($this->roles as $r) {
            if ($r->getName() === 'waiter') {
                return true;
            }
        }
        return false;
    }

    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function setHourlyRate(float $hourlyRate): void
    {
        $this->hourlyRate = $hourlyRate;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function setPassword(string $password): void
    {
        $this->passwordHash = password_hash($password, PASSWORD_BCRYPT);
    }

    public function setRoles(array $roles): void
    {
        $this->roles->clear();
        foreach ($roles as $role) {
            $this->roles->add($role);
        }
    }
}
