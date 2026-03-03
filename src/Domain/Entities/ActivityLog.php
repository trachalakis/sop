<?php

declare(strict_types=1);

namespace Domain\Entities;

use Datetime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\ActivityLogRepository')]
#[ORM\Table(name: 'activity_log')]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'datetime', name: 'date_time')]
    private Datetime $when;

    #[ORM\Column(type: 'string', name: 'what')]
    private string $what;

    #[ORM\Column(type: 'string', name: 'who')]
    private string $who;

    public function getId(): int
    {
        return $this->id;
    }

    public function getWhen(): Datetime
    {
        return $this->when;
    }
    
    public function getWhat(): string
    {
        return $this->what;
    }

    public function getWho(): string
    {
        return $this->who;
    }

    public function setWhen(Datetime $when): void
    {
        $this->when = $when;
    }

    public function setWhat(string $what): void
    {
        $this->what = $what;
    }

    public function setWho(string $who): void
    {
        $this->who = $who;
    }
}