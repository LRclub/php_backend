<?php

namespace App\Entity;

use App\Repository\NoticeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NoticeRepository::class)
 */
class Notice
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="notice")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $type;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_read = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $create_time;

    /**
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $category;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $data = [];

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_push_sended = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?user
    {
        return $this->user;
    }

    public function setUser(?user $user): self
    {
        $this->user = $user;

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

    public function setIsRead(?bool $is_read): self
    {
        $this->is_read = $is_read;

        return $this;
    }
    public function getIsRead(): ?bool
    {
        return $this->is_read;
    }

    public function getCreateTime(): ?int
    {
        return $this->create_time;
    }

    public function setCreateTime(int $create_time): self
    {
        $this->create_time = $create_time;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getIsPushSended(): ?bool
    {
        return $this->is_push_sended;
    }

    public function setIsPushSended(?bool $is_push_sended): self
    {
        $this->is_push_sended = $is_push_sended;

        return $this;
    }

    public function getCategoryTitle(): string
    {
        switch ($this->category) {
            case 'payment':
                return 'Подписка';
            case 'materials':
                return 'Материалы';
            case 'chat':
                return 'Чат';
            case 'comments':
                return 'Комментарий';
            case 'stream':
                return 'Эфир';
            case 'system':
                return 'Личный рай';
            default:
                return 'Личный рай';
        }
    }
}
