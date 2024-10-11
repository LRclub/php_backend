<?php

namespace App\Controller\Api\Admin\Specialists;

use App\Form\SiteSettingsType;
use App\Repository\SiteSettingsRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Form\Specialists\CategoriesCreateType;
use App\Services\Admin\AdminSpecialistsCategoriesServices;

class AdminCategoriesController extends BaseApiController
{
    /**
     * Создание категории для специалистов
     *
     * @Route("/api/admin/specialists/categories", name="api_admin_specialist_categories_create", methods={"PUT"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="name", type="string", description="Категория", example="Йога"),
     *       @OA\Property(property="specialists_ids",
     *          type="array",
     *          description="Идентификаторы специалистов",
     *          example="[1,2,4]",
     *          @OA\Items(type="integer", format="int32")
     *       ),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Категория для специалистов успешно добавлена")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin specialist categories")
     * @Security(name="Bearer")
     */
    public function adminSpecialistCreateAction(
        Request $request,
        AdminSpecialistsCategoriesServices $adminSpecialistsCategoriesServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $category = [
            'name' => (string)$this->getJson($request, 'name'),
            'specialists_ids' => (array)$this->getJson($request, 'specialists_ids'),
            'consultation_id' => null
        ];

        $form = $this->createFormByArray(CategoriesCreateType::class, $category);
        if ($form->isValid()) {
            try {
                $adminSpecialistsCategoriesServices->createSpecialistCategory($form);
            } catch (LogicException $e) {
                return $this->jsonError(['#' => $e->getMessage()], 400);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Категория для специалистов создана"]);
    }

    /**
     * Редактирование категории для специалистов
     *
     * @Route("/api/admin/specialists/categories", name="api_admin_specialist_categories_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="consultation_id", type="integer", description="ID категории", example="1"),
     *       @OA\Property(property="name", type="string", description="Категория", example="Йога"),
     *       @OA\Property(property="specialists_ids",
     *          type="array",
     *          description="Идентификаторы специалистов",
     *          example="[1,2,4]",
     *          @OA\Items(type="integer", format="int32")
     *       ),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Категория для специалистов успешно изменена")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin specialist categories")
     * @Security(name="Bearer")
     */
    public function adminSpecialistEditAction(
        Request $request,
        AdminSpecialistsCategoriesServices $adminSpecialistsCategoriesServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $category = [
            'name' => (string)$this->getJson($request, 'name'),
            'specialists_ids' => (array)$this->getJson($request, 'specialists_ids'),
            'consultation_id' => $this->getIntOrNull($this->getJson($request, 'consultation_id')) ?? 0
        ];

        $form = $this->createFormByArray(CategoriesCreateType::class, $category);
        if ($form->isValid()) {
            try {
                $adminSpecialistsCategoriesServices->editSpecialistCategory($form);
            } catch (LogicException $e) {
                return $this->jsonError(['#' => $e->getMessage()], 400);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Категория для специалистов изменена"]);
    }

    /**
     * Удаление категории для специалиста
     *
     * @Route("/api/admin/specialists/categories/{id}",
     *      requirements={"id"="\d+"},
     *      name="api_admin_specialist_categories_delete",
     *      methods={"DELETE"}
     * )
     *
     * @OA\Parameter(name="id", in="path", description="ID категории",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Категория для специалистов успешно удалена")
     * @OA\Response(response=401, description="Ошибка удаления")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin specialist categories")
     * @Security(name="Bearer")
     */
    public function adminSpecialistDeleteAction(
        Request $request,
        AdminSpecialistsCategoriesServices $adminSpecialistsCategoriesServices,
        CoreSecurity $security,
        int $id
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        try {
            $adminSpecialistsCategoriesServices->deleteSpecialistCategory($id);
        } catch (LogicException $e) {
            return $this->jsonError(["id" => $e->getMessage()], 400);
        }

        return $this->jsonSuccess(['result' => "Категория успешно удалена"]);
    }



    /**
     * Список категорий и специалистов для админ панели
     *
     * @Route("/api/admin/specialists/list", name="api_admin_specialists_categories_list", methods={"GET"})
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin specialist")
     * @Security(name="Bearer")
     */
    public function getSpecialistsCategoriesAdmin(
        CoreSecurity $security,
        Request $request,
        AdminSpecialistsCategoriesServices $adminSpecialistsCategoriesServices
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $result = $adminSpecialistsCategoriesServices->getCategoriesAdmin();

        return $this->jsonSuccess(['result' => $result]);
    }
}
