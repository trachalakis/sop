<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateInterval;
use Datetime;
use Domain\Entities\User;
use Domain\Repositories\ScansRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScansRepository::class)]
#[ORM\Table(name: 'scans')]
class Scan
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'datetime', name: 'check_in')]
    private Datetime $checkIn;

    #[ORM\Column(type: 'datetime', name: 'check_out')]
    private ?Datetime $checkOut;

    #[ORM\Column(type: 'float', name: 'hourly_rate')]
    private float $hourlyRate;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $roles;

    public function __construct(
        float $hourlyRate,
        Datetime $checkIn,
        ?Datetime $checkOut,
        User $user,
        ?array $roles = null
    ) {
        $this->setHourlyRate($hourlyRate);
        $this->setCheckIn($checkIn);
        $this->setCheckOut($checkOut);
        $this->setUser($user);
        $this->setRoles($roles);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCheckIn(): Datetime
    {
        return $this->checkIn;
    }

    public function getCheckOut(): ?Datetime
    {
        return $this->checkOut;
    }

    public function getHourlyRate(): ?float
    {
        return $this->hourlyRate;
    }

    public function getInterval(): ?DateInterval
    {
        if ($this->checkIn != null && $this->checkOut != null) {
            return $this->checkOut->diff($this->checkIn);
        }

        return null;
    }

    public function getSalary()
    {
        $salary = 0;

        if ($this->getInterval() != null) {
            $salary += ($this->getInterval()->h) * $this->hourlyRate;
            $salary += ($this->getInterval()->i / 60) * $this->hourlyRate;
        }

        return $salary;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setCheckIn(Datetime $checkIn)
    {
        $this->checkIn = $checkIn;
    }

    public function setCheckOut(?Datetime $checkOut)
    {
        $this->checkOut = $checkOut;
    }

    public function setHourlyRate(float $hourlyRate)
    {
        $this->hourlyRate = $hourlyRate;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(?array $roles): void
    {
        $this->roles = $roles;
    }
}