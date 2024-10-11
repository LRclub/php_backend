<?php

namespace App\Entity;

use App\Repository\PromocodesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass=PromocodesRepository::class)
 * @ORM\Table(name="promocodes",
 *    uniqueConstraints={
 *        @UniqueConstraint(name="code_unique",
 *            columns={"code", "action"})
 *    }
 *  )
 */
class Promocodes
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $action;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="promocodes")
     */
    private $owner;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="integer",  nullable=true)
     */
    private $start_time;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $end_time;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $amount;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_active = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_deleted = 0;

    /**
     * @ORM\OneToMany(targetEntity=PromocodesUsed::class, mappedBy="promocode")
     */
    private $promocodes_used;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $amount_used = 0;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $discount_percent;

    public function __construct()
    {
        $this->promocodes_used = new ArrayCollection();
    }

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

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

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

    public function getStartTime(): ?int
    {
        return $this->start_time;
    }

    public function setStartTime(int $start_time): self
    {
        $this->start_time = $start_time;

        return $this;
    }

    public function getEndTime(): ?int
    {
        return $this->end_time;
    }

    public function setEndTime(?int $end_time): self
    {
        $this->end_time = $end_time;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(?int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(?bool $is_active): self
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->is_deleted;
    }

    public function setIsDeleted(?bool $is_deleted): self
    {
        $this->is_deleted = $is_deleted;

        return $this;
    }

    /**
     * @return Collection<int, PromocodesUsed>
     */
    public function getPromocodesUsed(): Collection
    {
        return $this->promocodes_used;
    }

    public function addPromocodesUsed(PromocodesUsed $promocodesUsed): self
    {
        if (!$this->promocodes_used->contains($promocodesUsed)) {
            $this->promocodes_used[] = $promocodesUsed;
            $promocodesUsed->setPromocode($this);
        }

        return $this;
    }

    public function removePromocodesUsed(PromocodesUsed $promocodesUsed): self
    {
        if ($this->promocodes_used->removeElement($promocodesUsed)) {
            // set the owning side to null (unless already changed)
            if ($promocodesUsed->getPromocode() === $this) {
                $promocodesUsed->setPromocode(null);
            }
        }

        return $this;
    }

    public function getAmountUsed(): ?int
    {
        return $this->amount_used;
    }

    public function setAmountUsed(?int $amount_used): self
    {
        $this->amount_used = $amount_used;

        return $this;
    }

    public function getDiscountPercent(): ?int
    {
        return $this->discount_percent;
    }

    public function setDiscountPercent(?int $discount_percent): self
    {
        $this->discount_percent = $discount_percent;

        return $this;
    }
}
