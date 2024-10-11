<?php

namespace App\Entity;

use App\Repository\FeedbackMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FeedbackMessageRepository::class)
 */
class FeedbackMessage
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
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="integer")
     */
    private $create_time;

    /**
     * @ORM\Column(type="integer")
     */
    private $update_time;

    /**
     * @ORM\OneToMany(targetEntity=Files::class, mappedBy="feedback_message")
     */
    private $files;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_read = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $notification_sended = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_admin = 0;

    /**
     * @ORM\ManyToOne(targetEntity=Feedback::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $feedback;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_deleted = 0;

    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

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

    public function setUpdateTime(int $update_time): self
    {
        $this->update_time = $update_time;

        return $this;
    }

    /**
     * @return Collection|Files[]
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(Files $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files[] = $file;
            $file->setFeedbackMessage($this);
        }

        return $this;
    }

    public function removeFile(Files $file): self
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getFeedbackMessage() === $this) {
                $file->setFeedbackMessage(null);
            }
        }

        return $this;
    }

    public function getIsRead(): ?bool
    {
        return $this->is_read;
    }

    public function setIsRead(?bool $is_read = false): self
    {
        $this->is_read = $is_read;

        return $this;
    }

    public function getNotificationSended(): ?bool
    {
        return $this->notification_sended;
    }

    public function setNotificationSended(?bool $notification_sended): self
    {
        $this->notification_sended = $notification_sended;

        return $this;
    }

    public function getIsAdmin(): ?bool
    {
        if (!$this->is_admin) {
            $this->is_admin = false;
        }
        return $this->is_admin;
    }

    public function setIsAdmin(?bool $is_admin): self
    {
        $this->is_admin = $is_admin;

        return $this;
    }

    public function getFeedback(): ?Feedback
    {
        return $this->feedback;
    }

    public function setFeedback(?Feedback $feedback): self
    {
        $this->feedback = $feedback;

        return $this;
    }

    /**
     * Список данных
     *
     * @return array
     */
    public function getFeedbackMessageArrayData(): array
    {
        return [
            'message_id' => $this->id,
            'message' => $this->comment,
            'create_time' => $this->create_time ? $this->create_time : null,
            'update_time' => $this->update_time ? $this->update_time : null,
            'is_read' => boolval($this->is_read),
            'notification_sended' => boolval($this->notification_sended),
            'is_admin' => boolval($this->is_admin),
            'feedback_id' => $this->feedback->getId(),
            'is_deleted' => boolval($this->is_deleted)
        ];
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
}
