<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\UsersRepository')]
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

    #[ORM\Column(type: 'integer', name: 'monthly_credits')]
    private int $monthlyCredits;

    #[ORM\Column(type: 'string', name: 'notes')]
    private ?string $notes;

    #[ORM\Column(type: 'string', name: 'password_hash')]
    private string $passwordHash;

    #[ORM\Column(type: 'simple_array', name: 'roles')]
    private array $roles;

    #[ORM\Column(type: 'simple_array', name: 'allowed_menus')]
    private array $allowedMenus;

    public function __construct(
        bool $isActive,
        string $emailAddress,
        string $password,
        string $fullName,
        float $hourlyRate,
        array $roles
    ) {
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

    public function getAllowedMenus(): array
    {
        return $this->allowedMenus;
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

    public function getMonthlyCredits(): int
    {
        return $this->monthlyCredits;
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
        return $this->roles;
    }

    public function isEmployee(): bool
    {
        return in_array('employee', $this->roles);
    }

    public function isWaiter(): bool
    {
        return in_array('waiter', $this->roles);
    }

    public function setAllowedMenus(array $allowedMenus): void
    {
        $this->allowedMenus = $allowedMenus;
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
    
    public function setMonthlyCredits(int $monthlyCredits): void
    {
        $this->monthlyCredits = $monthlyCredits;
    }

    public function setPassword(string $password): void
    {
        $this->passwordHash = password_hash($password, PASSWORD_BCRYPT);
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }
}