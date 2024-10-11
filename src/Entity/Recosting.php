<?php

namespace App\Entity;

use App\Repository\RecostingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RecostingRepository::class)
 */
class Recosting
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
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_vip = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $subscription_from;

    /**
     * @ORM\Column(type="integer")
     */
    private $subscription_to;

    /**
     * @ORM\Column(type="float")
     */
    private $tariff_price;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $total_price;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $remaining_price;

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

    public function getIsVip(): ?bool
    {
        return $this->is_vip;
    }

    public function setIsVip(?bool $is_vip): self
    {
        $this->is_vip = $is_vip;

        return $this;
    }

    public function getSubscriptionFrom(): ?int
    {
        return $this->subscription_from;
    }

    public function setSubscriptionFrom(int $subscription_from): self
    {
        $this->subscription_from = $subscription_from;

        return $this;
    }

    public function getSubscriptionTo(): ?int
    {
        return $this->subscription_to;
    }

    public function setSubscriptionTo(int $subscription_to): self
    {
        $this->subscription_to = $subscription_to;

        return $this;
    }

    public function getTariffPrice(): ?float
    {
        return $this->tariff_price;
    }

    public function setTariffPrice(float $tariff_price): self
    {
        $this->tariff_price = $tariff_price;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->total_price;
    }

    public function setTotalPrice(?float $total_price): self
    {
        $this->total_price = $total_price;

        return $this;
    }

    public function getRemainingPrice(): ?float
    {
        return $this->remaining_price;
    }

    public function setRemainingPrice(?float $remaining_price): self
    {
        $this->remaining_price = $remaining_price;

        return $this;
    }
}
