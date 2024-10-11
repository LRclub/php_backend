<?php

namespace App\Entity;

use App\Repository\NotificationsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotificationsRepository::class)
 */
class Notifications
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="notifications", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $new_materials = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $subscription_history = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $email_notice;

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

    public function getNewMaterials(): ?bool
    {
        return $this->new_materials;
    }

    public function setNewMaterials(?bool $new_materials): self
    {
        $this->new_materials = $new_materials;

        return $this;
    }

    public function getSubscriptionHistory(): ?bool
    {
        return $this->subscription_history;
    }

    public function setSubscriptionHistory(?bool $subscription_history): self
    {
        $this->subscription_history = $subscription_history;

        return $this;
    }

    public function getEmailNotice(): ?bool
    {
        return $this->email_notice;
    }

    public function setEmailNotice(?bool $email_notice): self
    {
        $this->email_notice = $email_notice;

        return $this;
    }
}
