<?php

namespace App\Entity;

use App\Repository\ChangeUserPhoneRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ChangeUserPhoneRepository::class)
 */
class ChangeUserPhone
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
     * @ORM\Column(type="string", length=64)
     */
    private $code;

    /**
     * @ORM\Column(type="integer")
     */
    private $create_time;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $confirm_time;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_confirmed;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCreateTime(): ?int
    {
        return $this->create_time;
    }

    public function setCreateTime(int $create_time): self
    {
        $this->create_time = $create_time;

        return $this;
    }

    public function getConfirmTime(): ?int
    {
        return $this->confirm_time;
    }

    public function setConfirmTime(?int $confirm_time): self
    {
        $this->confirm_time = $confirm_time;

        return $this;
    }

    public function getIsConfirmed(): ?bool
    {
        return $this->is_confirmed;
    }

    public function setIsConfirmed(?bool $is_confirmed): self
    {
        $this->is_confirmed = $is_confirmed;

        return $this;
    }
}
