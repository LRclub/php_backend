<?php

namespace App\Entity;

use App\Repository\ChatUnreadCountRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ChatUnreadCountRepository::class)
 */
class ChatUnreadCount
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
     * @ORM\ManyToOne(targetEntity=Chat::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $chat;

    /**
     * @ORM\Column(type="integer")
     */
    private $update_time;

    /**
     * @ORM\ManyToOne(targetEntity=ChatMessage::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $last_message;

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

    public function getChat(): ?Chat
    {
        return $this->chat;
    }

    public function setChat(?Chat $chat): self
    {
        $this->chat = $chat;

        return $this;
    }

    public function getUpdateTime(): ?int
    {
        return $this->update_time;
    }

    public function setUpdateTime(int $update_time): self
    {
        $this->update_time = $update_time;

        return $this;
    }

    public function getLastMessage(): ?ChatMessage
    {
        return $this->last_message;
    }

    public function setLastMessage(?ChatMessage $last_message): self
    {
        $this->last_message = $last_message;

        return $this;
    }
}
