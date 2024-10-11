<?php

namespace App\Entity;

use App\Repository\PromocodesUsedRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PromocodesUsedRepository::class)
 */
class PromocodesUsed
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Promocodes::class, inversedBy="promocodes_used")
     * @ORM\JoinColumn(nullable=false)
     */
    private $promocode;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $activation_time;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPromocode(): ?Promocodes
    {
        return $this->promocode;
    }

    public function setPromocode(?Promocodes $promocode): self
    {
        $this->promocode = $promocode;

        return $this;
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

    public function getActivationTime(): ?int
    {
        return $this->activation_time;
    }

    public function setActivationTime(?int $activation_time): self
    {
        $this->activation_time = $activation_time;

        return $this;
    }
}
