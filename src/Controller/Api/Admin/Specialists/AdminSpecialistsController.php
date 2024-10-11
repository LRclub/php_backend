<?php

namespace App\Controller\Api\Admin\Specialists;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Base\BaseApiController;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Form\Specialists\SpecialistsCreateType;
use App\Services\Admin\AdminSpecialistsServices;

class AdminSpecialistsController extends BaseApiController
{
    /**
     * Создание специалиста
     *
     * @Route("/api/admin/specialists", name="api_admin_specialist_create", methods={"PUT"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="fio", type="string", description="ФИО", example="Иванов Иван Иванович"),
     *       @OA\Property(property="email", type="string", description="E-mail специалиста", example="email@test.ru"),
     *       @OA\Property(property="experience", type="string", description="Опыт", example="10 лет в этом деле"),
     *       @OA\Property(property="price", type="float", description="Цена услуг", example="1500"),
     *       @OA\Property(property="sort", type="integer", description="Приоритет сортировки", example="1"),
     *       @OA\Property(property="speciality", type="string", description="Специальность", example="Главный йог"),
     *       @OA\Property(property="avatar_id", type="integer", description="ID изображения", example="1")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Специалист успешно создан")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin specialist")
     * @Security(name="Bearer")
     */
    public function adminSpecialistCreateAction(
        Request $request,
        AdminSpecialistsServices $adminSpecialistsServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $specialist['fio'] = (string)$this->getJson($request, 'fio');
        $specialist['email'] = (string)$this->getJson($request, 'email');
        $specialist['experience'] = trim((string)$this->getJson($request, 'experience'));
        $specialist['speciality'] = trim((string)$this->getJson($request, 'speciality'));
        $specialist['price'] = (float)$this->getJson($request, 'price');
        $specialist['sort'] = (int)$this->getJson($request, 'sort');
        $specialist['avatar'] = $this->getJson($request, 'avatar_id');
        $specialist['specialist_id'] = null;
        $specialist['is_active'] = true;

        $form = $this->createFormByArray(SpecialistsCreateType::class, $specialist);
        if ($form->isValid()) {
            try {
                $adminSpecialistsServices->createSpecialist($form);
            } catch (LogicException $e) {
                return $this->jsonError(['#' => $e->getMessage()], 400);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Специалист успешно создан"]);
    }

    /**
     * Редактирование специалиста
     *
     * @Route("/api/admin/specialists", name="api_admin_specialist_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="specialist_id", type="integer", description="ID специалиста", example="1"),
     *       @OA\Property(property="fio", type="string", description="ФИО", example="Иванов Иван Иванович"),
     *       @OA\Property(property="email", type="string", description="E-mail специалиста", example="email@test.ru"),
     *       @OA\Property(property="experience", type="string", description="Опыт", example="10 лет в этом деле"),
     *       @OA\Property(property="price", type="float", description="Цена услуг", example="1500"),
     *       @OA\Property(property="sort", type="integer", description="Приоритет сортировки", example="1"),
     *       @OA\Property(property="speciality", type="string", description="Специальность", example="Главный йог"),
     *       @OA\Property(property="avatar_id", type="integer", description="ID изображения", example="1"),
     *       @OA\Property(property="is_active", type="boolean", description="Статус активности", example="1"),
     *
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Специалист успешно изменен")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin specialist")
     * @Security(name="Bearer")
     */
    public function adminSpecialistEditAction(
        Request $request,
        AdminSpecialistsServices $adminSpecialistsServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $specialist['fio'] = (string)$this->getJson($request, 'fio');
        $specialist['email'] = (string)$this->getJson($request, 'email');
        $specialist['experience'] = trim((string)$this->getJson($request, 'experience'));
        $specialist['speciality'] = trim((string)$this->getJson($request, 'speciality'));
        $specialist['price'] = (float)$this->getJson($request, 'price');
        $specialist['sort'] = (int)$this->getJson($request, 'sort');
        $specialist['avatar'] = $this->getJson($request, 'avatar_id');
        $specialist['specialist'] = $this->getIntOrNull($this->getJson($request, 'specialist_id')) ?? 0;
        $specialist['is_active'] = (bool)$this->getJson($request, 'is_active');

        $form = $this->createFormByArray(SpecialistsCreateType::class, $specialist);
        if ($form->isValid()) {
            try {
                $adminSpecialistsServices->editSpecialist($form);
            } catch (LogicException $e) {
                return $this->jsonError(['#' => $e->getMessage()], 400);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Специалист успешно изменен"]);
    }

    /**
     * Удаление специалиста
     *
     * @Route("/api/admin/specialists/{id}",
     *      requirements={"id"="\d+"},
     *      name="api_admin_specialist_delete",
     *      methods={"DELETE"}
     * )
     *
     * @OA\Parameter(name="id", in="path", description="ID специалиста",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Специалист успешно удален")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin specialist")
     * @Security(name="Bearer")
     */
    public function adminSpecialistDeleteAction(
        AdminSpecialistsServices $adminSpecialistsServices,
        CoreSecurity $security,
        int $id
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        try {
            $adminSpecialistsServices->deleteSpecialist($id);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()], 400);
        }

        return $this->jsonSuccess(['result' => "Специалист успешно удален"]);
    }

    /**
     * Список специалистов c пагинацией, сортировкой и поиском
     *
     * @Route("/api/admin/specialists", name="api_admin_specialists_list", methods={"GET"})
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin specialist")
     * @Security(name="Bearer")
     */
    public function adminSpecialistsListAction(
        CoreSecurity $security,
        Request $request,
        AdminSpecialistsServices $adminSpecialistsServices
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        // Сортировка
        $order_by['sort_param'] = mb_strtolower($request->query->get('sort'));
        $order = mb_strtolower($request->query->get('order'));
        $order_by['sort_type'] = ($order == self::SORT_ASC) ? self::SORT_ASC : self::SORT_DESC;
        $page = $this->getIntOrNull($request->query->get('page')) ?? 1;
        $search = trim($request->query->get('search'));

        $result = $adminSpecialistsServices->getSpecialistsList($page, $order_by, $search);

        return $this->jsonSuccess($result);
    }

    /**
     * Список всех специалистов для выпадающего меню
     *
     * @Route("/api/admin/specialists/all", name="api_admin_specialists_all", methods={"GET"})
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin specialist")
     * @Security(name="Bearer")
     */
    public function adminSpecialistsAllAction(
        CoreSecurity $security,
        AdminSpecialistsServices $adminSpecialistsServices
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $result = $adminSpecialistsServices->getSpecialistsAll();

        return $this->jsonSuccess([
            'specialists' => $result
        ]);
    }
}
