<?php

namespace App\Entity;

use App\Repository\JournalWorkbookRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=JournalWorkbookRepository::class)
 */
class JournalWorkbook
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
    private $goal;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $result;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $type;

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

    public function getGoal(): ?string
    {
        return $this->goal;
    }

    public function setGoal(?string $goal): self
    {
        $this->goal = $goal;

        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;

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

    public function getWorkbookArray(): array
    {
        return [
            'id' => $this->getId(),
            'result' => strval($this->getResult()),
            'goal' => strval($this->getGoal()),
            'date' => $this->getDate(),
        ];
    }
}
