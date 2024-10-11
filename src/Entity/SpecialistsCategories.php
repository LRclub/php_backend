<?php

namespace App\Entity;

use App\Repository\SpecialistsCategoriesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SpecialistsCategoriesRepository::class)
 */
class SpecialistsCategories
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Specialists::class, inversedBy="categories")
     * @ORM\JoinColumn(nullable=false)
     */
    private $specialist;

    /**
     * @ORM\ManyToOne(targetEntity=Consultations::class, inversedBy="specialists")
     * @ORM\JoinColumn(nullable=false)
     */
    private $consultation;

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getSpecialist(): ?Specialists
    {
        return $this->specialist;
    }

    public function setSpecialist(?Specialists $specialist): self
    {
        $this->specialist = $specialist;

        return $this;
    }

    public function getConsultation(): ?consultations
    {
        return $this->consultation;
    }

    public function setConsultation(?consultations $consultation): self
    {
        $this->consultation = $consultation;

        return $this;
    }
}
