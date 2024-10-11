<?php

namespace App\Entity;

use App\Repository\AuthCodeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AuthCodeRepository::class)
 */
class AuthCode
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=6)
     */
    private $code;

    /**
     * @ORM\Column(type="integer")
     */
    private $time;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $attempts;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $IP;

    /**
     * @ORM\Column(type="string", length=15)
     */
    private $phone;

    /**
     * @ORM\ManyToOne(targetEntity=Promocodes::class)
     */
    private $promo;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_completed;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_mailed;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $role;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getAttempts(): ?int
    {
        return $this->attempts;
    }

    public function setAttempts(?int $attempts): self
    {
        $this->attempts = $attempts;

        return $this;
    }

    public function getIP(): ?string
    {
        return $this->IP;
    }

    public function setIP(string $IP): self
    {
        $this->IP = $IP;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPromo(): ?Promocodes
    {
        return $this->promo;
    }

    public function setPromo(?Promocodes $promo): self
    {
        $this->promo = $promo;

        return $this;
    }

    public function getIsCompleted(): ?bool
    {
        return $this->is_completed;
    }

    public function setIsCompleted(bool $is_completed): self
    {
        $this->is_completed = $is_completed;

        return $this;
    }

    public function getIsMailed(): ?bool
    {
        return $this->is_mailed;
    }

    public function setIsMailed(?bool $is_mailed): self
    {
        $this->is_mailed = $is_mailed;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }
}
