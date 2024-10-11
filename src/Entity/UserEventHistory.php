<?php

namespace App\Entity;

use App\Repository\UserEventHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserEventHistoryRepository::class)
 */
class UserEventHistory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     */
    private $completion_time;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $action;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $amount = 0;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ab_test;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $device_type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $user_agent;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $device_os;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $platform;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCompletionTime(): ?int
    {
        return $this->completion_time;
    }

    public function setCompletionTime(int $completion_time): self
    {
        $this->completion_time = $completion_time;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAbTest(): ?string
    {
        return $this->ab_test;
    }

    public function setAbTest(?string $ab_test): self
    {
        $this->ab_test = $ab_test;

        return $this;
    }

    public function getDeviceType(): ?string
    {
        return $this->device_type;
    }

    public function setDeviceType(?string $device_type): self
    {
        $this->device_type = $device_type;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    public function setUserAgent(?string $user_agent): self
    {
        $this->user_agent = $user_agent;

        return $this;
    }

    public function getDeviceOs(): ?string
    {
        return $this->device_os;
    }

    public function setDeviceOs(?string $device_os): self
    {
        $this->device_os = $device_os;

        return $this;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(?string $platform): self
    {
        $this->platform = $platform;

        return $this;
    }
}
