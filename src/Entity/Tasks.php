<?php

namespace App\Entity;

use App\Repository\TasksRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TasksRepository::class)
 */
class Tasks
{
    public const TYPE_TIME_PAST = 'past';
    public const TYPE_TIME_UPCOMING = 'upcoming';
    public const TYPE_TIME_TODAY = 'today';
    public const TYPE_COMPLETED = 'completed';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="tasks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $task_time;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_deleted = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_completed = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $create_time;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTaskTime(): ?int
    {
        return $this->task_time;
    }

    public function setTaskTime(?int $task_time): self
    {
        $this->task_time = $task_time;

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

    public function getIsCompleted(): ?bool
    {
        return $this->is_completed;
    }

    public function setIsCompleted(?bool $is_completed): self
    {
        $this->is_completed = $is_completed;

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

    /**
     * Данные объекта в виде массива
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'task_time' => $this->getTaskTime(),
            'is_completed' => $this->getIsCompleted(),
            'create_time' => $this->getCreateTime(),
            'type_time' => $this->getTypeTime()
        ];
    }

    /**
     * Возвращаем тип времени таска к какому он относится
     * На случай, если отменяем как выполненный, чтобы определить в какой блок вернуть (сегодня, запланированные, прошедшие)
     *
     * @return string
     */
    public function getTypeTime(): string
    {
        $start_day = strtotime(date('Y-m-d'));
        $end_day = strtotime(date('Y-m-d 23:59:59'));

        if ($this->getTaskTime() && $this->getTaskTime() >= $start_day && $this->getTaskTime() <= $end_day) {
            return self::TYPE_TIME_TODAY;
        } else if ($this->getTaskTime() && $this->getTaskTime() <= time()) {
            return self::TYPE_TIME_PAST;
        }

        return self::TYPE_TIME_UPCOMING;
    }
}
