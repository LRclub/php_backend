<?php

namespace App\Controller\Api\Admin\Promocodes;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Controller\Base\BaseApiController;
use OpenApi\Annotations as OA;
use App\Form\Promocodes\PromocodeType;
use App\Services\Marketing\PromocodeServices;
use Symfony\Component\HttpFoundation\Response;

class AdminPromocodesController extends BaseApiController
{
    /**
     * Api создания промокода
     *
     * @Route("/api/admin/promocode", name="api_admin_promocode_create", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="code", type="string", description="Промокод", example="SFD33"),
     *       @OA\Property(property="action", type="string", description="Экшн", example="invite"),
     *       @OA\Property(property="description", type="string", description="Описание", example="Скидка 10%"),
     *       @OA\Property(property="start_time", type="string", description="С", example="2023-01-01"),
     *       @OA\Property(property="end_time", type="string", description="До", example="2023-12-01"),
     *       @OA\Property(property="amount", type="integer", description="Лимит использований", example="50"),
     *       @OA\Property(property="is_active", type="integer", description="Включен", example="1"),
     *       @OA\Property(property="discount_percent", type="integer", description="Процент скидки", example="50"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Промокод успешно создан")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin promocodes")
     * @Security(name="Bearer")
     */
    public function adminCreatePromocodeAction(
        Request $request,
        PromocodeServices $promocodeServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $promocode_data['code'] = (string)$this->getJson($request, 'code');
        $promocode_data['description'] = (string)$this->getJson($request, 'description');
        $promocode_data['start_time'] = (string)$this->getJson($request, 'start_time');
        $promocode_data['end_time'] = (string)$this->getJson($request, 'end_time');
        $promocode_data['amount'] = (int)$this->getJson($request, 'amount');
        $promocode_data['is_active'] = (int)$this->getJson($request, 'is_active');
        $promocode_data['action'] = (string)$this->getJson($request, 'action');
        $promocode_data['discount_percent'] = $this->getIntOrNull($this->getJson($request, 'discount_percent'));

        $form = $this->createFormByArray(PromocodeType::class, $promocode_data);
        if ($form->isValid()) {
            $promocodeServices->createPromocode($form);
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Промокод успешно создан"]);
    }

    /**
     * Api удаления промокода
     *
     * @Route("/api/admin/promocode/{id}",
     *      requirements={"id"="\d+"},
     *      name="api_admin_promocode_delete",
     *      methods={"DELETE"}
     *    )
     *
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID промокода",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Промокод успешно удален")
     * @OA\Response(response=400, description="Промокод не найден")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin promocodes")
     * @Security(name="Bearer")
     */
    public function adminDeletePromocodeAction(
        Request $request,
        PromocodeServices $promocodeServices,
        CoreSecurity $security,
        int $id
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        try {
            $promocodeServices->deletePromocode($id);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()], 400);
        }
        return $this->jsonSuccess(['result' => "Промокод успешно удален"]);
    }

    /**
     * Api приостановки промокода
     *
     * @Route("/api/admin/promocode/active", name="api_admin_promocode_active", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="promocode_id", type="integer", description="ID", example="1"),
     *       @OA\Property(property="is_active", type="boolean", description="Статус активности", example="true"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Статус активации изменен")
     * @OA\Response(response=400, description="Промокод не найден")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin promocodes")
     * @Security(name="Bearer")
     */
    public function adminIsActivePromocodeAction(
        Request $request,
        PromocodeServices $promocodeServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $promocode_id = (int)$this->getJson($request, 'promocode_id');
        $is_active = (bool)$this->getJson($request, 'is_active');

        try {
            $promocodeServices->setIsActivePromocode($promocode_id, $is_active);
        } catch (LogicException $e) {
            return $this->jsonError(['promocode_id' => $e->getMessage()], 400);
        }
        return $this->jsonSuccess(['result' => "Статус активации изменен"]);
    }

    /**
     * Api получения промокода по ID
     *
     * @Route("/api/admin/promocode/{id}", requirements={"id"="\d+"}, name="api_admin_promocode_info", methods={"GET"})
     *
     * @OA\Parameter(name="id", in="path", description="ID промокода",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Данные успешно получены")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin promocodes")
     * @Security(name="Bearer")
     */
    public function adminPromocodeInfoAction(
        $id,
        PromocodeServices $promocodeServices,
        CoreSecurity $security
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }
        $result = $promocodeServices->getPromocodeById(intval($id))[0];
        if (!$result) {
            return $this->jsonError(["id" => "Промокод с таким id не найден!"], 400);
        }

        return $this->jsonSuccess(['result' => $result]);
    }

    /**
     * Api редактирования промокода
     *
     * @Route("/api/admin/promocode", name="api_admin_promocode_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="promocode_id", type="integer", description="ID", example="1"),
     *       @OA\Property(property="code", type="string", description="Промокод", example="SFD33"),
     *       @OA\Property(property="action", type="string", description="Экшн", example="invite"),
     *       @OA\Property(property="description", type="string", description="Описание", example="Скидка 10%"),
     *       @OA\Property(property="start_time", type="string", description="С", example="2023-01-01"),
     *       @OA\Property(property="end_time", type="string", description="До", example="2023-12-01"),
     *       @OA\Property(property="amount", type="integer", description="Лимит использований", example="50"),
     *       @OA\Property(property="discount_percent", type="integer", description="Процент скидки", example="50"),
     *       @OA\Property(property="is_active", type="integer", description="Включен", example="1"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Промокод успешно изменен")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin promocodes")
     * @Security(name="Bearer")
     */
    public function adminEditPromocodeAction(
        Request $request,
        PromocodeServices $promocodeServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $promocode_data['promocode_id'] = (int)$this->getJson($request, 'promocode_id');
        $promocode_data['code'] = (string)$this->getJson($request, 'code');
        $promocode_data['description'] = (string)$this->getJson($request, 'description');
        $promocode_data['start_time'] = (string)$this->getJson($request, 'start_time');
        $promocode_data['end_time'] = (string)$this->getJson($request, 'end_time');
        $promocode_data['amount'] = (int)$this->getJson($request, 'amount');
        $promocode_data['is_active'] = (int)$this->getJson($request, 'is_active');
        $promocode_data['action'] = (string)$this->getJson($request, 'action');
        $promocode_data['discount_percent'] = $this->getIntOrNull($this->getJson($request, 'discount_percent'));

        $form = $this->createFormByArray(PromocodeType::class, $promocode_data);

        if ($form->isValid()) {
            $promocodeServices->editPromocode($form);
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Промокод успешно отредактирован"]);
    }
}
