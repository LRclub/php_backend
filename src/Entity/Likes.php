<?php

namespace App\Entity;

use App\Repository\LikesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LikesRepository::class)
 */
class Likes
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=LikesCollector::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $likes_collector;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_like = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLikesCollector(): ?LikesCollector
    {
        return $this->likes_collector;
    }

    public function setLikesCollector(?LikesCollector $likes_collector): self
    {
        $this->likes_collector = $likes_collector;

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

    public function getIsLike(): ?bool
    {
        return $this->is_like;
    }

    public function setIsLike(?bool $is_like): self
    {
        $this->is_like = $is_like;

        return $this;
    }
}
