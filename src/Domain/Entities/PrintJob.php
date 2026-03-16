<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\PrintJobsRepository;
use Domain\Enums\PrintJobStatus;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: PrintJobsRepository::class)]
#[ORM\Table(name: 'print_jobs')]
class PrintJob
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', name: 'printer')]
    private string $printer;

    #[ORM\Column(type: 'string', enumType: PrintJobStatus::class, name: 'status')]
    private PrintJobStatus $status;

    #[ORM\Column(type: 'string', name: 'xml')]
    private string $xml;

    public function getId()
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPrinter(): string
    {
        return $this->printer;
    }
    
    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function getXml(): string
    {
        return $this->xml;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setPrinter(string $printer): void
    {
        $this->printer = $printer;
    }

    public function setStatus(PrintJobStatus $status): void
    {
        $this->status = $status;
    }

    public function setXml(string $xml): void
    {
        $this->xml = $xml;
    }
}