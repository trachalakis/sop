<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'Domain\Repositories\StationsRepository')]
#[ORM\Table(name: 'stations')]
class Station
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'boolean', name: 'has_receipt_printer')]
    private bool $hasReceiptPrinter;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'string', name: 'name', unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', name: 'printer_address')]
    private string $printerAddress;

    public function __construct(string $name, string $printerAddress)
    {
        $this->setName($name);
        $this->setPrinterAddress($printerAddress);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getHasReceiptPrinter(): bool
    {
    	return $this->hasReceiptPrinter;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

	public function getName(): string
    {
        return $this->name;
    }

	public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPrinterAddress(): string
    {
        return $this->printerAddress;
    }

    public function setHasReceiptPrinter(bool $hasReceiptPrinter): void
    {
    	$this->hasReceiptPrinter = $hasReceiptPrinter;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

	public function setPrinterAddress(string $printerAddress): void
    {
        $this->printerAddress = $printerAddress;
    }
}