<?php

namespace App\Services\Admin;

use App\Entity\TrackerTemplate;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TrackerTemplateRepository;
use Symfony\Component\Security\Core\Exception\LogicException;

class AdminTrackerTemplateServices
{
    private EntityManagerInterface $em;
    private TrackerTemplateRepository $trackerTemplateRepository;

    public function __construct(
        EntityManagerInterface $em,
        TrackerTemplateRepository $trackerTemplateRepository
    ) {
        $this->em = $em;
        $this->trackerTemplateRepository = $trackerTemplateRepository;
    }

    /**
     * Список стандартных задач для трекера
     *
     * @return [type]
     */
    public function getDefaultTrackerTasks()
    {
        $result = [];
        $tasks = $this->trackerTemplateRepository->findBy(['is_deleted' => false]);
        if (!($tasks)) {
            return $result;
        }

        foreach ($tasks as $task) {
            $result[] = [
                'id' => $task->getId(),
                'name' => $task->getName()
            ];
        }

        return $result;
    }

    /**
     * Получение стандартной задачи по ID
     *
     * @param int $id
     *
     * @return [type]
     */
    public function getDefaultTrackerTaskById(int $id)
    {
        $result = [];
        $task = $this->trackerTemplateRepository->findOneBy([
            'is_deleted' => false,
            'id' => $id
        ]);
        if (!($task)) {
            return $result;
        }

        $result[] = [
            'id' => $task->getId(),
            'name' => $task->getName()
        ];


        return $result;
    }

    /**
     * Редактирование шаблона задачи для трекера
     *
     * @param mixed $form
     *
     * @return TrackerTemplate
     */
    public function updateTask($form): TrackerTemplate
    {
        $tracker_template = $form->get('template_id')->getData();
        $name = $form->get('name')->getData();

        $tracker_template->setName($name);
        $this->em->persist($tracker_template);
        $this->em->flush();

        return $tracker_template;
    }

    /**
     * Создание шаблона задачи для трекера
     *
     * @param mixed $form
     *
     * @return TrackerTemplate
     */
    public function createTask($form): TrackerTemplate
    {
        $tracker_template = new TrackerTemplate();
        $name = $form->get('name')->getData();

        $tracker_template->setName($name);
        $this->em->persist($tracker_template);
        $this->em->flush();

        return $tracker_template;
    }

    public function deleteTask($id): TrackerTemplate
    {
        $tracker_template = $this->trackerTemplateRepository->find((int)$id);
        if (!$tracker_template) {
            throw new LogicException('Задача не найдена');
        }

        if ($tracker_template->getIsDeleted()) {
            throw new LogicException('Задача уже удалена');
        }

        $tracker_template->setIsDeleted(true);
        $this->em->persist($tracker_template);
        $this->em->flush();

        return $tracker_template;
    }
}
