<?php

declare(strict_types=1);

namespace Domain\Entities;

use DateInterval;
use Datetime;
use Domain\Entities\User;

/**
 * @Entity(repositoryClass="Domain\Repositories\ScansRepository")
 * @Table(name="scans")
 **/
class Scan
{
	/**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Column(type="datetime", name="check_in")
     */
    private Datetime $checkIn;

    /**
     * @Column(type="datetime", name="check_out")
     */
    private ?Datetime $checkOut;

    /**
     * @Column(type="float", name="hourly_rate")
     */
    private float $hourlyRate;

    /**
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    private User $user;

    public function __construct(
        float $hourlyRate,
        Datetime $checkIn,
        ?Datetime $checkOut,
        User $user
    ) {
        $this->setHourlyRate($hourlyRate);
        $this->setCheckIn($checkIn);
        $this->setCheckOut($checkOut);
        $this->setUser($user);
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
}