<?php

namespace App\Entity;

use App\Repository\CommentsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommentsRepository::class)
 */
class Comments
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
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $moderation_status;

    /**
     * @ORM\ManyToOne(targetEntity=CommentsCollector::class, inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $comments_collector;

    /**
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_deleted = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $create_time;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $update_time;

    /**
     * @ORM\ManyToOne(targetEntity=LikesCollector::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $likes_collector;

    /**
     * @ORM\ManyToOne(targetEntity=Comments::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $reply;

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

    public function getModerationStatus(): ?int
    {
        return $this->moderation_status;
    }

    public function setModerationStatus(?int $moderation_status): self
    {
        $this->moderation_status = $moderation_status;

        return $this;
    }

    public function getCommentsCollector(): ?CommentsCollector
    {
        return $this->comments_collector;
    }

    public function setCommentsCollector(?CommentsCollector $comments_collector): self
    {
        $this->comments_collector = $comments_collector;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

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

    public function getCreateTime(): ?int
    {
        return $this->create_time;
    }

    public function setCreateTime(int $create_time): self
    {
        $this->create_time = $create_time;

        return $this;
    }

    public function getUpdateTime(): ?int
    {
        return $this->update_time;
    }

    public function setUpdateTime(?int $update_time): self
    {
        $this->update_time = $update_time;

        return $this;
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

    public function getReply(): ?Comments
    {
        return $this->reply;
    }

    public function setReply(?Comments $reply): self
    {
        $this->reply = $reply;

        return $this;
    }

    /**
     * Массив данных для админ панели
     *
     * @return [type]
     */
    public function getAdminArrayData()
    {
        return [
            'id' => $this->getId(),
            'is_deleted' => $this->getIsDeleted(),
            'user_id' => $this->getUser()->getId(),
            'first_name' => $this->getUser()->getFirstName(),
            'last_name' => $this->getUser()->getLastName(),
            'text' => $this->getText(),
            'create_time' => $this->getCreateTime(),
            'create_time_formatted' => date('Y-m-d H:i:s', $this->getCreateTime()),
            'update_time' => $this->getUpdateTime()
        ];
    }
}
