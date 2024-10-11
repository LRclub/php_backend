<?php

namespace App\Services\Calendar;

use App\Entity\User;
use App\Repository\CommentsCollectorRepository;
use Symfony\Component\Security\Core\Exception\LogicException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Repository\TasksRepository;
use App\Repository\MaterialsRepository;
use App\Services\Materials\MaterialsServices;

class CalendarServices
{
    private CommentsCollectorRepository $messagesCollectorRepository;
    private EntityManagerInterface $em;
    private CoreSecurity $security;
    private TasksRepository $tasksRepository;
    private MaterialsRepository $materialsRepository;
    private MaterialsServices $materialsServices;

    private const COLOR_TASKS = "#F3EBEB"; //розовый
    private const COLOR_MATERIALS = "#618388"; //бирюзовый
    private const COLOR_MATERIALS_AND_TASKS = "#DFEDED"; //светло бирюзовый
    private const UNIQ_TASKS = "tasks";
    private const UNIQ_MATERIALS = "materials";
    private const UNIQ_MATERIALS_AND_TASKS = "tasks_materials";

    public function __construct(
        CommentsCollectorRepository $messagesCollectorRepository,
        EntityManagerInterface $em,
        CoreSecurity $security,
        TasksRepository $tasksRepository,
        MaterialsRepository $materialsRepository,
        MaterialsServices $materialsServices
    ) {
        $this->messagesCollectorRepository = $messagesCollectorRepository;
        $this->em = $em;
        $this->security = $security;
        $this->tasksRepository = $tasksRepository;
        $this->materialsRepository = $materialsRepository;
        $this->materialsServices = $materialsServices;
    }

    /**
     * Получение данных для календаря
     *
     * @param User $user
     * @param mixed $date
     *
     * @return [type]
     */
    public function getCalendarList(User $user, $date)
    {
        if (!(bool)preg_match("/^[0-9]{4}-(0[1-9]|1[012])$/", $date)) {
            throw new LogicException("Введите корректную дату в формате YYYY-MM");
        }

        $date = strtotime($date);
        $result = [];
        if (!$date) {
            throw new LogicException("Введите корректную дату в формате YYYY-MM");
        }

        // Проверка диапазона по годам (допустим +- 1 год)
        $year = intval(date("Y", $date));
        if ($year < intval(date("Y") - 1)) {
            throw new LogicException("Нужно указать " . intval(date("Y") - 1) . " год или выше");
        }
        if ($year > intval(date("Y") + 1)) {
            throw new LogicException("Нужно указать " . intval(date("Y") + 1) . " год или меньше");
        }

        // Список дней в месяце
        $days_count =  (int)date('t', mktime(0, 0, 0, date('m', $date), 1, date('Y', $date)));

        // Получение материалов или задач для заполнения
        $tasks = $this->tasksRepository->getUserMonthTasks($user, $date);
        $materials = $this->materialsRepository->getCalendarMaterials($user, $date, true);

        $i = 1;
        while ($i <= $days_count) {
            $colors = [];
            // Задачи
            if ($tasks) {
                foreach ($tasks as $task) {
                    if ((int)date('d', $task->getTaskTime()) == $i) {
                        $result[$i]['color'] = $this->getColorDate(null, self::COLOR_TASKS);
                        $result[$i]['uniq_items'] = $this->getColorDate(null, self::UNIQ_TASKS);
                        $result[$i]['tasks'][] = [
                            'id' => $task->getId(),
                            'name' => $task->getName(),
                            'task_time' => $task->getTaskTime(),
                            'task_time_formatted' => $task->getTaskTime() ? date('Y-m-d', $task->getTaskTime()) : null,
                            'is_completed' => $task->getIsCompleted(),
                            'create_time' => $task->getCreateTime()
                        ];
                    }
                }
            }

            if ($materials) {
                foreach ($materials as $material) {
                    $day = null;
                    if ((int)date('d', $material->getCreateTime()) == $i) {
                        $day = $i;
                    }

                    if ($material->getLazyPost()) {
                        if ((int)date('d', $material->getLazyPost()) == $i) {
                            $day = $i;
                        }
                    }

                    if ($day) {
                        $result[$i]['color'] = $this->getColorDate(
                            $result[$i]['color'] ?? null,
                            self::COLOR_MATERIALS,
                            self::COLOR_MATERIALS_AND_TASKS
                        );
                        $result[$i]['uniq_items'] = $this->getColorDate(
                            $result[$i]['uniq_items'] ?? null,
                            self::UNIQ_MATERIALS,
                            self::UNIQ_MATERIALS_AND_TASKS
                        );

                        switch ($material->getType()) {
                            case MaterialsServices::TYPE_ARTICLE:
                                $result[$i][MaterialsServices::TYPE_ARTICLE][]
                                    = $this->materialsServices->getMaterialInfo($material, $user);
                                break;
                            case MaterialsServices::TYPE_AUDIO:
                                $result[$i][MaterialsServices::TYPE_AUDIO][] =
                                    $this->materialsServices->getMaterialInfo($material, $user);
                                break;
                            case MaterialsServices::TYPE_VIDEO:
                                $result[$i][MaterialsServices::TYPE_VIDEO][] =
                                    $this->materialsServices->getMaterialInfo($material, $user);
                                break;
                            case MaterialsServices::TYPE_STREAM:
                                $result[$i][MaterialsServices::TYPE_STREAM][] =
                                    $this->materialsServices->getMaterialInfo($material, $user);
                                break;
                            case MaterialsServices::TYPE_MEDITATION:
                                $result[$i][MaterialsServices::TYPE_MEDITATION][] =
                                    $this->materialsServices->getMaterialInfo($material, $user);
                                break;
                        }
                    }
                }
            }

            $i++;
        }

        return $result;
    }

    public function getColorDate(?string $old_value, ?string $new_value = null, ?string $combo_value = null): string
    {
        if (is_null($old_value)) {
            return $new_value;
        }

        //предполагаем, что это самый "максимальный" цвет, после него ничего не меняем
        if ($old_value == $combo_value) {
            return $old_value;
        }

        //если 2 разных типа события, то бирюзовый втыкаем
        if ($old_value != $new_value) {
            return $combo_value;
        }

        return $old_value;
    }
}
