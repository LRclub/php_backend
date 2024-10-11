<?php

namespace App\Controller\Api\Tracker;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\Exception\LogicException;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\Tracker\TrackerServices;
use App\Form\Tracker\TrackerCreateType;

class TrackerController extends BaseApiController
{
    /**
     * Получение списка задач за неделю для трекера
     *
     * @Route("/api/tracker", name="api_panel_tracker_info", methods={"GET"})
     *
     * @OA\Get(path="/api/panel/tracker?", operationId="getTrackerTask"),
     *
     * @OA\Parameter(
     *              in="query", name="date_from",
     *               schema={"type"="string", "example"="2023-05-01"},
     *              description="Дата начала недели"
     *              ),
     * @OA\Parameter(
     *              in="query", name="date_to",
     *              schema={"type"="string", "example"="2023-05-07"},
     *              description="Дата окончания недели"
     *              )
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=400, description="Ошибка даты")
     *
     * @OA\Tag(name="tracker")
     * @Security(name="Bearer")
     */
    public function getTrackerTask(
        Request $request,
        CoreSecurity $security,
        TrackerServices $trackerServices
    ) {
        $user = $security->getUser();

        $date = [
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to')
        ];

        try {
            $tasks = $trackerServices->getTrackerTasks($user, $date);
        } catch (LogicException $e) {
            return $this->jsonError(['error' => $e->getMessage()]);
        }

        return $this->jsonSuccess(['result' => $tasks]);
    }

    /**
     * Создание задачи для трекера
     *
     * @Route("/api/tracker", name="api_panel_tracker_create", methods={"PUT"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="name", type="string", description="Название задачи", example="Пробежать 100 метров")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Задача успешно создана")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Ошибка")
     *
     * @OA\Tag(name="tracker")
     * @Security(name="Bearer")
     */
    public function createTrackerTaskAction(
        Request $request,
        CoreSecurity $security,
        TrackerServices $trackerServices
    ): Response {
        $user = $security->getUser();
        $tracker = [
            'name' => strval($this->getJson($request, 'name')),
            'tracker_id' => null
        ];

        $form = $this->createFormByArray(TrackerCreateType::class, $tracker);
        if ($form->isValid()) {
            $task = $trackerServices->createTask($user, $form);
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => true, 'id' => $task->getId()]);
    }

    /**
     * Редактирование задачи для трекера
     *
     * @Route("/api/tracker", name="api_panel_tracker_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="tracker_id", type="integer", description="ID задачи", example="1"),
     *       @OA\Property(property="name", type="string", description="Название задачи", example="Пробежать 100 метров")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Задача успешно отредактирована")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Ошибка")
     *
     * @OA\Tag(name="tracker")
     * @Security(name="Bearer")
     */
    public function editTrackerTaskAction(
        Request $request,
        CoreSecurity $security,
        TrackerServices $trackerServices
    ): Response {
        $user = $security->getUser();
        $tracker = [
            'name' => strval($this->getJson($request, 'name')),
            'tracker_id' =>  (int)$this->getJson($request, 'tracker_id')
        ];

        $form = $this->createFormByArray(TrackerCreateType::class, $tracker);

        if ($form->isValid()) {
            try {
                $task = $trackerServices->editTask($user, $form);
            } catch (LogicException $e) {
                return $this->jsonError(['name' => $e->getMessage()], 404);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => true, 'id' => $task->getId()]);
    }

    /**
     * Удалить задачу для трекера
     *
     * @Route("/api/tracker/{id}", requirements={"id"="\d+"}, name="api_panel_tracker_delete", methods={"DELETE"})
     *
     * @OA\Parameter(name="id", in="path", description="ID задачи",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Задача успешно удалена")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Ошибка")
     *
     * @OA\Tag(name="tracker")
     * @Security(name="Bearer")
     */
    public function deleteTrackerTaskAction(
        Request $request,
        CoreSecurity $security,
        TrackerServices $trackerServices,
        int $id
    ): Response {
        $user = $security->getUser();

        try {
            $task = $trackerServices->deleteTask($user, $id);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()]);
        }

        return $this->jsonSuccess(['result' => true, 'id' => $task->getId()]);
    }

    /**
     * Задать статус задаче (отметка о выполнении)
     *
     * @Route("/api/tracker", name="api_panel_tracker_status", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="tracker_id", type="integer", description="ID задачи", example="1"),
     *       @OA\Property(property="status", type="integer", description="Статус 1 или 2", example="1"),
     *       @OA\Property(property="date", type="string", description="Дата выполнения", example="2023-05-04"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Статус задачи изменен")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Ошибка")
     *
     * @OA\Tag(name="tracker")
     * @Security(name="Bearer")
     */
    public function setTaskStatus(
        Request $request,
        CoreSecurity $security,
        TrackerServices $trackerServices
    ) {
        $user = $security->getUser();
        $tracker_id = (int)$this->getJson($request, 'tracker_id');
        $status = (int)$this->getJson($request, 'status');
        $date = (string)$this->getJson($request, 'date');

        try {
            $trackerServices->setStatus($user, $tracker_id, $status, $date);
        } catch (LogicException $e) {
            return $this->jsonError(['tracker_id' => $e->getMessage()]);
        }

        return $this->jsonSuccess(['result' => true]);
    }
}
