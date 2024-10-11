<?php

namespace App\Entity;

use App\Repository\SubscriptionHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SubscriptionHistoryRepository::class)
 */
class SubscriptionHistory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="subscriptionHistories")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $price;

    /**
     * @ORM\Column(type="integer")
     */
    private $create_time;

    /**
     * @ORM\Column(type="integer")
     */
    private $subscription_from;

    /**
     * @ORM\Column(type="integer")
     */
    private $subscription_to;

    /**
     * @ORM\ManyToOne(targetEntity=Invoice::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $invoice;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_vip = 0;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

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

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(Invoice $invoice): self
    {
        $this->invoice = $invoice;

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
}
