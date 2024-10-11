<?php

namespace App\Controller\Api\Admin\Tracker;

use App\Form\Tracker\TrackerTemplateCreateType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Base\BaseApiController;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\Admin\AdminTrackerTemplateServices;

class TrackerTemplateController extends BaseApiController
{
    /**
     * Api редактирования шаблона для трекера
     *
     * @Route("/api/admin/tracker", name="api_admin_tracker_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="template_id", type="integer", description="template_id", example="1"),
     *       @OA\Property(property="name", type="string", description="Название задачи", example="Попить воды")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Задача успешно изменена")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin tracker template")
     * @Security(name="Bearer")
     */
    public function adminEditTrackerTemplateAction(
        Request $request,
        AdminTrackerTemplateServices $adminTrackerTemplateServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $tracker['template_id'] = $this->getIntOrNull($this->getJson($request, 'template_id')) ?? 0;
        $tracker['name'] = (string)$this->getJson($request, 'name');

        $form = $this->createFormByArray(TrackerTemplateCreateType::class, $tracker);

        if ($form->isValid()) {
            try {
                $adminTrackerTemplateServices->updateTask($form);
            } catch (LogicException $e) {
                return $this->jsonError(['template_id' => $e->getMessage()], 400);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Задача успешно изменена"]);
    }

    /**
     * Api создания шаблона для трекера
     *
     * @Route("/api/admin/tracker", name="api_admin_tracker_create", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="name", type="string", description="Название задачи", example="Отдохнуть")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Задача успешно создана")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin tracker template")
     * @Security(name="Bearer")
     */
    public function adminCreateTaskTemplateAction(
        Request $request,
        AdminTrackerTemplateServices $adminTrackerTemplateServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $tracker['template_id'] = null;
        $tracker['name'] = (string)$this->getJson($request, 'name');

        $form = $this->createFormByArray(TrackerTemplateCreateType::class, $tracker);

        if ($form->isValid()) {
            try {
                $adminTrackerTemplateServices->createTask($form);
            } catch (LogicException $e) {
                return $this->jsonError(["template_id" => $e->getMessage()], 400);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Задача успешно создана"]);
    }

    /**
     * Api удаления шаблона задачи
     *
     * @Route("/api/admin/tracker/{id}",
     *      requirements={"id"="\d+"},
     *      name="api_admin_tracker_delete",
     *      methods={"DELETE"}
     * )
     *
     * @OA\Parameter(name="id", in="path", description="ID настройки",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Параметр успешно удален")
     * @OA\Response(response=401, description="Ошибка удаления")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin tracker template")
     * @Security(name="Bearer")
     */
    public function deleteAction(
        AdminTrackerTemplateServices $adminTrackerTemplateServices,
        CoreSecurity $security,
        int $id
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        try {
            $adminTrackerTemplateServices->deleteTask($id);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()], 400);
        }

        return $this->jsonSuccess(['result' => "Шаблон задачи успешно удален"]);
    }

    /**
     * Список шаблонов задач для трекера
     *
     * @Route("/api/tracker/template", name="api_tracker_template", methods={"GET"})
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=401, description="Необходима авторизация")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin tracker template")
     * @Security(name="Bearer")
     */
    public function getTemplateTrackerTasksAction(
        AdminTrackerTemplateServices $adminTrackerTemplateServices
    ) {
        $result = $adminTrackerTemplateServices->getDefaultTrackerTasks();

        return $this->jsonSuccess(['result' => $result]);
    }
}
