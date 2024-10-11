<?php

namespace App\Entity;

use App\Repository\FilesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FilesRepository::class)
 */
class Files
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
     * @ORM\Column(type="integer")
     */
    private $create_time;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $file_path;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_deleted;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $delete_time;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $file_type;

    /**
     * @ORM\ManyToOne(targetEntity=FeedbackMessage::class, inversedBy="files")
     */
    private $feedback_message;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_image = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_active = 0;
    /**
     * @ORM\ManyToOne(targetEntity=ChatMessage::class)
     */
    private $chat_message;
    /**
     * @ORM\ManyToOne(targetEntity=Materials::class)
     */
    private $materials;
    /**
     * @ORM\ManyToOne(targetEntity=Specialists::class, inversedBy="avatar")
     */
    private $specialist;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_audio = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_video = 0;


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

    public function getCreateTime(): ?int
    {
        return $this->create_time;
    }

    public function setCreateTime(int $create_time): self
    {
        $this->create_time = $create_time;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->file_path;
    }

    public function setFilePath(string $file_path): self
    {
        $this->file_path = $file_path;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->is_deleted;
    }

    public function setIsDeleted(bool $is_deleted): self
    {
        $this->is_deleted = $is_deleted;

        return $this;
    }

    public function getDeleteTime(): ?int
    {
        return $this->delete_time;
    }

    public function setDeleteTime(?int $delete_time): self
    {
        $this->delete_time = $delete_time;

        return $this;
    }

    public function getFileType(): ?string
    {
        return $this->file_type;
    }

    public function setFileType(string $file_type): self
    {
        $this->file_type = $file_type;

        return $this;
    }

    public function getFeedbackMessage(): ?FeedbackMessage
    {
        return $this->feedback_message;
    }

    public function setFeedbackMessage(?FeedbackMessage $feedback_message): self
    {
        $this->feedback_message = $feedback_message;

        return $this;
    }

    public function getIsImage(): ?bool
    {
        return $this->is_image;
    }

    public function setIsImage(?bool $is_image): self
    {
        $this->is_image = $is_image;

        return $this;
    }

    public function getFilename(): string
    {
        return basename($this->file_path);
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(?bool $is_active): self
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function getChatMessage(): ?ChatMessage
    {
        return $this->chat_message;
    }

    public function setChatMessage(?ChatMessage $chat_message): self
    {
        $this->chat_message = $chat_message;
        return $this;
    }

    public function getMaterials(): ?Materials
    {
        return $this->materials;
    }

    public function setMaterials(?Materials $materials): self
    {
        $this->materials = $materials;
        return $this;
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

    public function getIsAudio(): ?bool
    {
        return $this->is_audio;
    }

    public function setIsAudio(?bool $is_audio): self
    {
        $this->is_audio = $is_audio;

        return $this;
    }

    public function getIsVideo(): ?bool
    {
        return $this->is_video;
    }

    public function setIsVideo(?bool $is_video): self
    {
        $this->is_video = $is_video;

        return $this;
    }

    public function getFileAsArray()
    {
        $format = "";
        if ($this->getIsImage()) {
            $format = 'image';
        }

        if ($this->getIsAudio()) {
            $format = 'audio';
        }

        if ($this->getIsVideo()) {
            $format = 'video';
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getFileName(),
            'path' => $this->getFilePath(),
            'type' => $this->getFileType(),
            'format' => $format,
            'is_image' => $this->getIsImage(),
            'is_audio' => $this->getIsAudio(),
            'is_video' => $this->getIsVideo(),
            'extension' => substr(strrchr($this->getFileName(), '.'), 1),

        ];
    }
}
