<?php

namespace App\Entity;

use App\Repository\MaterialsStreamAccessRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MaterialsStreamAccessRepository::class)
 */
class MaterialsStreamAccess
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
     * @ORM\ManyToOne(targetEntity=Materials::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $material;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $user_key;
/**
     * @ORM\Column(type="string", length=255)
     */
    private $stream_url;

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

    public function getMaterial(): ?Materials
    {
        return $this->material;
    }

    public function setMaterial(?Materials $material): self
    {
        $this->material = $material;

        return $this;
    }

    public function getUserKey(): ?string
    {
        return $this->user_key;
    }

    public function setUserKey(string $user_key): self
    {
        $this->user_key = $user_key;

        return $this;
    }

    public function getStreamUrl(): ?string
    {
        return $this->stream_url;
    }

    public function setStreamUrl(string $stream_url): self
    {
        $this->stream_url = $stream_url;
        return $this;
    }
}
