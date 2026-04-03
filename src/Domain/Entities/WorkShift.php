<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\WorkShiftsRepository;

#[ORM\Entity(repositoryClass: WorkShiftsRepository::class)]
#[ORM\Table(name: 'work_shifts')]
class WorkShift
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: DailyRoleSlot::class)]
    #[ORM\JoinColumn(name: 'daily_role_slot_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private DailyRoleSlot $dailyRoleSlot;

    #[ORM\Column(type: 'datetime', name: 'start_time')]
    private DateTime $start;

    #[ORM\Column(type: 'datetime', name: 'end_time')]
    private DateTime $end;

    public function __construct(User $user, DailyRoleSlot $dailyRoleSlot, DateTime $start, DateTime $end)
    {
        $this->setUser($user);
        $this->setDailyRoleSlot($dailyRoleSlot);
        $this->setStart($start);
        $this->setEnd($end);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDailyRoleSlot(): DailyRoleSlot
    {
        return $this->dailyRoleSlot;
    }

    public function getStart(): DateTime
    {
        return $this->start;
    }

    public function getEnd(): DateTime
    {
        return $this->end;
    }

    public function getHours(): float
    {
        $interval = $this->end->diff($this->start);
        return ($interval->days * 24) + $interval->h + ($interval->i / 60);
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setDailyRoleSlot(DailyRoleSlot $dailyRoleSlot): void
    {
        $this->dailyRoleSlot = $dailyRoleSlot;
    }

    public function setStart(DateTime $start): void
    {
        $this->start = $start;
    }

    public function setEnd(DateTime $end): void
    {
        $this->end = $end;
    }
}
