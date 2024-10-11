<?php

namespace App\Entity;

use App\Repository\LikesCollectorRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LikesCollectorRepository::class)
 */
class LikesCollector
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Materials::class)
     */
    private $material;

    /**
     * @ORM\ManyToOne(targetEntity=Comments::class)
     */
    private $comment;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaterial(): ?Materials
    {
        return $this->material;
    }

    public function setMaterial(?Materials $material): self
    {
        $this->material = $material;

        return $this;
    }

    public function getComment(): ?Comments
    {
        return $this->comment;
    }

    public function setComment(?Comments $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
