<?php

namespace App\Services\Tasks;

use Amp\Parallel\Worker\Task;
use App\Entity\Tasks;
use App\Repository\TasksRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\LogicException;

class TasksServices
{
    private TasksRepository $tasksRepository;
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em,
        TasksRepository $tasksRepository
    ) {
        $this->em = $em;
        $this->tasksRepository = $tasksRepository;
    }

    /**
     * Создание таска
     *
     * @param mixed $user
     * @param mixed $form
     *
     * @return [type]
     */
    public function createTask($user, $form): Tasks
    {
        $name = $form->get('name')->getData();
        $task_time = $form->get('task_time')->getData();

        $task = new Tasks();
        $task
            ->setUser($user)
            ->setName($name)
            ->setTaskTime($task_time ? $task_time->getTimestamp() : null)
            ->setCreateTime(time());

        $this->em->persist($task);
        $this->em->flush();

        return $task;
    }

    /**
     * Редактирование таска
     *
     * @param mixed $user
     * @param mixed $form
     *
     * @return [type]
     */
    public function editTask($user, $form): Tasks
    {
        $task = $form->get('task_id')->getData();
        $name = $form->get('name')->getData();
        $task_time = $form->get('task_time')->getData();

        $task
            ->setUser($user)
            ->setName($name)
            ->setTaskTime($task_time ? $task_time->getTimestamp() : null)
            ->setCreateTime(time());

        $this->em->persist($task);
        $this->em->flush();

        return $task;
    }

    /**
     * Изменить статус задачи
     *
     * @param mixed $user
     * @param mixed $task_id
     * @param mixed $status
     *
     * @return [type]
     */
    public function completeTask($user, $task_id, $status)
    {
        if (empty($task_id)) {
            throw new LogicException('Нужно указать ID задачи');
        }

        $task = $this->tasksRepository->find($task_id);

        if (empty($task)) {
            throw new LogicException('Задача не найдена');
        }

        if ($task->getUser()->getId() != $user->getId()) {
            throw new LogicException('Вы не можете изменить эту задачу!');
        }

        if ($task->getIsDeleted()) {
            throw new LogicException('Задача удалена');
        }

        $task->setIsCompleted($status);
        $this->em->persist($task);
        $this->em->flush();

        return $task;
    }

    /**
     * Удалить таск
     *
     * @param mixed $user
     * @param mixed $task_id
     *
     * @return bool
     */
    public function deleteTask($user, $task_id): Tasks
    {
        if (empty($task_id)) {
            throw new LogicException('Нужно указать ID задачи');
        }

        $task = $this->tasksRepository->find($task_id);

        if (empty($task)) {
            throw new LogicException('Задача не найдена');
        }

        if ($task->getUser()->getId() != $user->getId()) {
            throw new LogicException('Вы не можете удалить эту задачу!');
        }

        if ($task->getIsDeleted()) {
            throw new LogicException('Задача уже удалена');
        }

        $task->setIsDeleted(true);
        $this->em->persist($task);
        $this->em->flush();

        return $task;
    }

    /**
     * @param mixed $user
     *
     * @return [type]
     */
    public function getTasks($user)
    {
        $result = [];
        $tasks = $this->tasksRepository->findBy([
            'user' => $user->getId(),
            'is_deleted' => false
        ], ['task_time' => 'ASC']);

        if (empty($tasks)) {
            return $result;
        }

        $result = [];
        $start_day = strtotime(date('Y-m-d'));
        $end_day = strtotime(date('Y-m-d 23:59:59'));

        foreach ($tasks as $task) {
            $type = null;
            if (!$task->getIsCompleted() || $task->getTaskTime() <= time()) {
                $type = Tasks::TYPE_TIME_PAST;
            }

            if (!$task->getTaskTime() || $task->getTaskTime() >= $start_day) {
                $type = Tasks::TYPE_TIME_UPCOMING;
            }

            if ($task->getTaskTime() && $task->getTaskTime() >= $start_day && $task->getTaskTime() <= $end_day) {
                $type = Tasks::TYPE_TIME_TODAY;
            }

            if ($task->getIsCompleted()) {
                $type = Tasks::TYPE_COMPLETED;
            }

            $result[$type][] = $task->toArray();
        }

        return $result;
    }
}
