<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\DailyRoleSlotsRepository;

#[ORM\Entity(repositoryClass: DailyRoleSlotsRepository::class)]
#[ORM\Table(name: 'daily_role_slots')]
class DailyRoleSlot
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'date', name: 'date')]
    private DateTime $date;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id')]
    private Role $role;

    public function __construct(DateTime $date, Role $role)
    {
        $this->date = $date;
        $this->role = $role;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function getRole(): Role
    {
        return $this->role;
    }
}
