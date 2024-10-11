<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InvoiceRepository::class)
 */
class Invoice
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="invoices")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="integer")
     */
    private $create_time;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $payment_system;

    /**
     * @ORM\ManyToOne(targetEntity=Tariffs::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $tariff;

    /**
     * @ORM\ManyToOne(targetEntity=Promocodes::class)
     */
    private $promocode;
/**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_recurring = 0;
/**
     * @ORM\ManyToOne(targetEntity=invoice::class)
     */
    private $recurrent_parent;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_auto = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $recurring_last_time;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_canceled = 0;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $recurring_attempts = 0;

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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

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

    public function getPaymentSystem(): ?string
    {
        return $this->payment_system;
    }

    public function setPaymentSystem(string $payment_system): self
    {
        $this->payment_system = $payment_system;

        return $this;
    }

    public function getTariff(): ?tariffs
    {
        return $this->tariff;
    }

    public function setTariff(?tariffs $tariff): self
    {
        $this->tariff = $tariff;

        return $this;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getIsRecurring(): ?bool
    {
        return $this->is_recurring;
    }

    public function setIsRecurring(?bool $is_recurring): self
    {
        $this->is_recurring = $is_recurring;

        return $this;
    }

    public function getRecurrentParent(): ?invoice
    {
        return $this->recurrent_parent;
    }

    public function setRecurrentParent(?invoice $recurrent_parent): self
    {
        $this->recurrent_parent = $recurrent_parent;
        return $this;
    }

    public function getIsAuto(): ?bool
    {
        return $this->is_auto;
    }

    public function setIsAuto(?bool $is_auto): self
    {
        $this->is_auto = $is_auto;

        return $this;
    }

    public function getRecurringLastTime(): ?int
    {
        return $this->recurring_last_time;
    }

    public function setRecurringLastTime(?int $recurring_last_time): self
    {
        $this->recurring_last_time = $recurring_last_time;

        return $this;
    }

    public function getIsCanceled(): ?bool
    {
        return $this->is_canceled;
    }

    public function setIsCanceled(?bool $is_canceled): self
    {
        $this->is_canceled = $is_canceled;

        return $this;
    }

    public function getRecurringAttempts(): ?int
    {
        return $this->recurring_attempts;
    }

    public function setRecurringAttempts(?int $recurring_attempts): self
    {
        $this->recurring_attempts = $recurring_attempts;

        return $this;
    }
}
