<?php

namespace App\Entity;

use App\Repository\SpecialistsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SpecialistsRepository::class)
 */
class Specialists
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fio;

    /**
     * @ORM\Column(type="text")
     */
    private $experience;

    /**
     * @ORM\Column(type="float")
     */
    private $price = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sort;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_active = 0;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $speciality;

    /**
     * @ORM\OneToMany(targetEntity=Files::class, mappedBy="specialist")
     */
    private $avatar;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_deleted = 0;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;
/**
     * @ORM\OneToMany(targetEntity=SpecialistsCategories::class, mappedBy="specialist", orphanRemoval=true)
     */
    private $categories;

    public function __construct()
    {
        $this->avatar = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFio(): ?string
    {
        return $this->fio;
    }

    public function setFio(string $fio): self
    {
        $this->fio = $fio;

        return $this;
    }

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(string $experience): self
    {
        $this->experience = $experience;

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

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(?int $sort): self
    {
        $this->sort = $sort;

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

    public function getSpeciality(): ?string
    {
        return $this->speciality;
    }

    public function setSpeciality(string $speciality): self
    {
        $this->speciality = $speciality;

        return $this;
    }

    /**
     * @return Collection<int, Files>
     */
    public function getAvatar(): Collection
    {
        return $this->avatar;
    }

    public function addAvatar(Files $avatar): self
    {
        if (!$this->avatar->contains($avatar)) {
            $this->avatar[] = $avatar;
            $avatar->setSpecialist($this);
        }

        return $this;
    }

    public function removeAvatar(Files $avatar): self
    {
        if ($this->avatar->removeElement($avatar)) {
            // set the owning side to null (unless already changed)
            if ($avatar->getSpecialist() === $this) {
                $avatar->setSpecialist(null);
            }
        }

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, SpecialistsCategories>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(SpecialistsCategories $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
            $category->setSpecialist($this);
        }

        return $this;
    }

    public function removeCategory(SpecialistsCategories $category): self
    {
        if ($this->categories->removeElement($category)) {
// set the owning side to null (unless already changed)
            if ($category->getSpecialist() === $this) {
                $category->setSpecialist(null);
            }
        }

        return $this;
    }
}
