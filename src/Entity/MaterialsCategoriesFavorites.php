<?php

namespace App\Entity;

use App\Repository\MaterialsCategoriesFavoritesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MaterialsCategoriesFavoritesRepository::class)
 */
class MaterialsCategoriesFavorites
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="materials_categories_favorites")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=MaterialsCategories::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

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

    public function getCategory(): ?MaterialsCategories
    {
        return $this->category;
    }

    public function setCategory(?MaterialsCategories $category): self
    {
        $this->category = $category;

        return $this;
    }
}
