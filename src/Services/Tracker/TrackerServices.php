<?php

namespace App\Services\Tracker;

// Entity
use App\Entity\Tracker;
use App\Entity\TrackerActions;
use App\Entity\User;
// Repository
use App\Repository\TrackerRepository;
use App\Repository\TrackerActionsRepository;
// Services
use App\Services\DateServices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\LogicException;

class TrackerServices
{
    private EntityManagerInterface $em;
    private TrackerRepository $trackerRepository;
    private TrackerActionsRepository $trackerActionsRepository;
    private DateServices $dateServices;

    public function __construct(
        EntityManagerInterface $em,
        TrackerRepository $trackerRepository,
        TrackerActionsRepository $trackerActionsRepository,
        DateServices $dateServices
    ) {
        $this->em = $em;
        $this->trackerRepository = $trackerRepository;
        $this->trackerActionsRepository = $trackerActionsRepository;
        $this->dateServices = $dateServices;
    }

    /**
     * Получение задач по дням
     *
     * @param User $user
     * @param mixed $date
     *
     * @return [type]
     */
    public function getTrackerTasks(User $user, $date)
    {
        $this->dateServices->validateWeekRange($date);

        $tasks = $this->trackerRepository->findBy([
            'user' => $user,
            'is_deleted' => 0
        ]);

        if (!$tasks) {
            return [];
        }

        $result = [];
        foreach ($tasks as $task) {
            $day_week = 0;
            $result[$task->getId()] = [
                'id' => $task->getId(),
                'name' => $task->getName(),
            ];
            while ($day_week !== 7) {
                $unix_day = strtotime("+$day_week days", strtotime($date['date_from']));
                $actions = $this->trackerActionsRepository->getWeekActions($user, $task, $unix_day);
                $status = 0;
                if ($actions) {
                    $status = intval($actions->getStatus());
                }
                $result[$task->getId()]['days'][date('D', $unix_day)] = [
                    'status' => $status,
                    'date' => date('Y-m-d', $unix_day)
                ];

                $day_week++;
            }
        }

        return $result;
    }

    /**
     * Создать задачу для трекера
     *
     * @param User $user
     * @param mixed $form
     *
     * @return [type]
     */
    public function createTask(User $user, $form)
    {
        $name = $form->get('name')->getData();

        $tracker = new Tracker();
        $tracker->setName($name)->setUser($user);

        $this->em->persist($tracker);
        $this->em->flush();

        return $tracker;
    }

    /**
     * Редактировать задачу для трекера
     *
     * @param User $user
     * @param mixed $form
     *
     * @return [type]
     */
    public function editTask(User $user, $form)
    {
        $name = $form->get('name')->getData();
        $tracker = $form->get('tracker_id')->getData();

        if ($tracker->getIsDeleted()) {
            throw new LogicException("Задача удалена");
        }

        if ($user != $tracker->getUser()) {
            throw new LogicException("Ошибка прав доступа");
        }

        $tracker->setName($name);
        $this->em->persist($tracker);
        $this->em->flush();

        return $tracker;
    }

    /**
     * Удаление задачи
     *
     * @param User $user
     * @param mixed $task_id
     *
     * @return [type]
     */
    public function deleteTask(User $user, $task_id)
    {
        if (!$task_id) {
            throw new LogicException("Нужно указать ID задачи");
        }

        $tracker = $this->trackerRepository->find($task_id);
        if (!$tracker) {
            throw new LogicException("Задача не найдена");
        }

        if ($tracker->getUser() != $user) {
            throw new LogicException("Ошибка доступа");
        }

        if ($tracker->getIsDeleted()) {
            throw new LogicException("Задача уже была удалена");
        }

        $tracker->setIsDeleted(true);
        $this->em->persist($tracker);
        $this->em->flush();

        return $tracker;
    }

    /**
     * Сменить статус задачи
     *
     * @param User $user
     * @param mixed $task_id
     * @param mixed $status
     *
     * @return [type]
     */
    public function setStatus(User $user, $task_id, $status, $date)
    {
        if (!$task_id) {
            throw new LogicException("Нужно указать ID задачи");
        }

        $tracker = $this->trackerRepository->find($task_id);

        if (!$tracker) {
            throw new LogicException("Задача не найдена");
        }

        if ($tracker->getUser() != $user) {
            throw new LogicException("Ошибка доступа");
        }

        if ($tracker->getIsDeleted()) {
            throw new LogicException("Задача уже был удалена");
        }

        if (strtotime($date)) {
            if (strtotime($date) > time()) {
                throw new LogicException("День выполнения задачи еще не настал");
            }
        } else {
            throw new LogicException("Ошибка даты");
        }

        $action = $this->trackerActionsRepository->findOneBy([
            'user' => $user,
            'action' => $tracker->getId(),
            'completion_date' => $date
        ]);

        if (!$action) {
            $action = new TrackerActions();
            $action->setUser($user)->setAction($tracker)->setStatus($status)->setCompletionDate($date);
        } else {
            $action->setStatus($status);
        }

        $this->em->persist($action);
        $this->em->flush();
        return $action;
    }
}
