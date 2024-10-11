<?php

namespace App\Entity;

use App\Repository\ConsultationsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConsultationsRepository::class)
 */
class Consultations
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
    private $name;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_deleted = 0;

    /**
     * @ORM\OneToMany(targetEntity=SpecialistsCategories::class, mappedBy="consultation", orphanRemoval=true)
     */
    private $specialists;

    public function __construct()
    {
        $this->specialists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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
     * @return Collection<int, SpecialistsCategories>
     */
    public function getSpecialists(): Collection
    {
        return $this->specialists;
    }

    public function addSpecialist(SpecialistsCategories $specialist): self
    {
        if (!$this->specialists->contains($specialist)) {
            $this->specialists[] = $specialist;
            $specialist->setConsultation($this);
        }

        return $this;
    }

    public function removeSpecialist(SpecialistsCategories $specialist): self
    {
        if ($this->specialists->removeElement($specialist)) {
            // set the owning side to null (unless already changed)
            if ($specialist->getConsultation() === $this) {
                $specialist->setConsultation(null);
            }
        }

        return $this;
    }
}
