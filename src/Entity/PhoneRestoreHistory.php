<?php

namespace App\Entity;

use App\Repository\PhoneRestoreHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PhoneRestoreHistoryRepository::class)
 */
class PhoneRestoreHistory
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
     * @ORM\Column(type="string", length=180)
     */
    private $last_phone;

    /**
     * @ORM\Column(type="string", length=180)
     */
    private $new_phone;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $update_time;

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

    public function getLastPhone(): ?string
    {
        return $this->last_phone;
    }

    public function setLastPhone(string $last_phone): self
    {
        $this->last_phone = $last_phone;

        return $this;
    }

    public function getNewPhone(): ?string
    {
        return $this->new_phone;
    }

    public function setNewPhone(string $new_phone): self
    {
        $this->new_phone = $new_phone;

        return $this;
    }

    public function getUpdateTime(): ?string
    {
        return $this->update_time;
    }

    public function setUpdateTime(string $update_time): self
    {
        $this->update_time = $update_time;

        return $this;
    }
}
