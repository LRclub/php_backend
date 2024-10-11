<?php

namespace App\Entity;

use App\Repository\ChatRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ChatRepository::class)
 */
class Chat
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $first_user;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $second_user;
/**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $preview_image;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_vip;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

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

    public function getFirstUser(): ?User
    {
        return $this->first_user;
    }

    public function setFirstUser(?User $first_user): self
    {
        $this->first_user = $first_user;

        return $this;
    }

    public function getSecondUser(): ?User
    {
        return $this->second_user;
    }

    public function setSecondUser(?User $second_user): self
    {
        $this->second_user = $second_user;

        return $this;
    }

    public function getPreviewImage(): ?string
    {
        return $this->preview_image;
    }

    public function setPreviewImage(?string $preview_image): self
    {
        $this->preview_image = $preview_image;
        return $this;
    }

    public function isVip(): ?bool
    {
        return $this->is_vip;
    }

    public function setIsVip(bool $is_vip): self
    {
        $this->is_vip = $is_vip;

        return $this;
    }
}
