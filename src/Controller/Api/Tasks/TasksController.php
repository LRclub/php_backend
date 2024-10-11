<?php

namespace App\Controller\Api\Tasks;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\Payment\SubscriptionHistoryServices;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Form\Tasks\CreateType;
use App\Services\Tasks\TasksServices;

class TasksController extends BaseApiController
{
    /**
     * Добавление задачи
     *
     * @Route("/api/task", name="api_task_add", methods={"PUT"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="name", type="string", description="Название задачи", example="Пробежка"),
     *       @OA\Property(property="task_time", type="string", description="Время", example="2023-06-20")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Задача успешно создана")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Ошибка")
     *
     * @OA\Tag(name="tasks")
     * @Security(name="Bearer")
     */
    public function createTask(
        CoreSecurity $security,
        Request $request,
        TasksServices $tasksServices
    ) {
        $user = $security->getUser();
        $task = [
            'name' => (string)$this->getJson($request, 'name'),
            'task_time' => (string)$this->getJson($request, 'task_time') ?? null
        ];

        $form = $this->createFormByArray(CreateType::class, $task);
        if ($form->isValid()) {
            $task = $tasksServices->createTask($user, $form);
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess([
            'result' => $task->toArray(),
            'id' => $task->getId()
        ]);
    }

    /**
     * Редактирование задачи
     *
     * @Route("/api/task", name="api_task_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="task_id", type="integer", description="ID задачи", example="1"),
     *       @OA\Property(property="name", type="string", description="Название задачи", example="Хоккей"),
     *       @OA\Property(property="task_time", type="string", description="Время", example="2023-06-20"),
     *       @OA\Property(property="is_completed", type="boolval", description="Задача выполнена", example="false"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Задача успешно отредактирована")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Ошибка")
     *
     * @OA\Tag(name="tasks")
     * @Security(name="Bearer")
     */
    public function editTask(
        CoreSecurity $security,
        Request $request,
        TasksServices $tasksServices
    ) {
        $user = $security->getUser();
        $task = [
            'task_id' => $this->getIntOrNull($this->getJson($request, 'task_id')),
            'name' => (string)$this->getJson($request, 'name'),
            'task_time' => (string)$this->getJson($request, 'task_time') ?? null
        ];

        $form = $this->createFormByArray(CreateType::class, $task);
        if ($form->isValid()) {
            $task = $tasksServices->editTask($user, $form);
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => $task->toArray()]);
    }

    /**
     * Удаление задачи
     *
     * @Route("/api/task/{id}", requirements={"id"="\d+"}, name="api_task_delete", methods={"DELETE"})
     *
     * @OA\Parameter(name="id", in="path", description="ID задачи",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Задача успешно удалена")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Ошибка")
     *
     * @OA\Tag(name="tasks")
     * @Security(name="Bearer")
     */
    public function deleteTask(
        CoreSecurity $security,
        Request $request,
        TasksServices $tasksServices,
        int $id
    ) {
        $user = $security->getUser();
        try {
            $task = $tasksServices->deleteTask($user, $id);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()]);
        }
        return $this->jsonSuccess(['result' => true]);
    }

    /**
     * Сменить статус выполнения задачи
     *
     * @Route("/api/task/completed", name="api_task_completed", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="task_id", type="integer", description="ID задачи", example="1"),
     *       @OA\Property(property="is_completed", type="boolval", description="Задача выполнена", example="false"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Статус выполнения изменен")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Ошибка")
     *
     * @OA\Tag(name="tasks")
     * @Security(name="Bearer")
     */
    public function completedTask(
        CoreSecurity $security,
        Request $request,
        TasksServices $tasksServices
    ) {
        $user = $security->getUser();
        $task_id = $this->getIntOrNull($this->getJson($request, 'task_id'));
        $status = (bool)$this->getJson($request, 'is_completed');

        try {
            $task = $tasksServices->completeTask($user, $task_id, $status);
        } catch (LogicException $e) {
            return $this->jsonError(['task_id' => $e->getMessage()]);
        }
        return $this->jsonSuccess([
            'result' => $task->toArray(),
            'status' => $status
        ]);
    }

    /**
     * Получение списка задач
     *
     * @Route("/api/tasks", name="api_task_list", methods={"GET"})
     *
     * @OA\Response(response=200, description="Информация получена")
     *
     * @OA\Tag(name="tasks")
     * @Security(name="Bearer")
     */
    public function getUserTasks(
        CoreSecurity $security,
        TasksServices $tasksServices
    ) {
        $user = $security->getUser();

        $tasks = $tasksServices->getTasks($user);
        return $this->jsonSuccess(['result' => $tasks]);
    }
}
