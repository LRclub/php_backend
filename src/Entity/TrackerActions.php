<?php

namespace App\Entity;

use App\Repository\TrackerActionsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TrackerActionsRepository::class)
 */
class TrackerActions
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
     * @ORM\Column(type="string", length=255)
     */
    private $status = 0;

    /**
     * @ORM\ManyToOne(targetEntity=Tracker::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $action;
/**
     * @ORM\Column(type="string", length=100)
     */
    private $completion_date;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAction(): ?Tracker
    {
        return $this->action;
    }

    public function setAction(?Tracker $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getCompletionDate(): ?string
    {
        return $this->completion_date;
    }

    public function setCompletionDate(string $completion_date): self
    {
        $this->completion_date = $completion_date;
        return $this;
    }
}
