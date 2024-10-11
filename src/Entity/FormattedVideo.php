<?php

namespace App\Entity;

use App\Repository\FormattedVideoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FormattedVideoRepository::class)
 */
class FormattedVideo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Files::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $file;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $file_path;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $convertation_status;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $start_time;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $end_time;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFile(): ?Files
    {
        return $this->file;
    }

    public function setFile(?Files $file): self
    {
        $this->file = $file;

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

    public function getConvertationStatus(): ?int
    {
        return $this->convertation_status;
    }

    public function setConvertationStatus(?int $convertation_status): self
    {
        $this->convertation_status = $convertation_status;

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

    public function getStartTime(): ?string
    {
        return $this->start_time;
    }

    public function setStartTime(string $start_time): self
    {
        $this->start_time = $start_time;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->end_time;
    }

    public function setEndTime(?string $end_time): self
    {
        $this->end_time = $end_time;

        return $this;
    }
}
