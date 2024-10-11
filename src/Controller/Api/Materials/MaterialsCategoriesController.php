<?php

namespace App\Controller\Api\Materials;

use App\Controller\Base\BaseApiController;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\LogicException;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use App\Form\Materials\MaterialsCategoryCreateType;
use App\Form\Materials\MaterialsCategoryEditType;
use App\Services\Materials\MaterialsCategoriesServices;

class MaterialsCategoriesController extends BaseApiController
{
    /**
     * Добавление категории материала
     *
     * @Route("/api/material/category", name="api_material_category_add", methods={"PUT"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="name", type="string", description="Название категории", example="Йога"),
     *       @OA\Property(property="slug", type="string", description="ЧПУ названия", example="yoga"),
     *       @OA\Property(property="parent_id", type="integer", description="ID родителя", example="2"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Категория успешно создана")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Ошибка")
     *
     * @OA\Tag(name="admin materials categories")
     * @Security(name="Bearer")
     */
    public function categoryAddAction(
        Request $request,
        CoreSecurity $security,
        MaterialsCategoriesServices $materialsCategoriesServices
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsEditor() && !$user->getIsAdmin()) {
            return $this->jsonError(['roles' => "Запрещено, нет прав"], 403);
        }

        $parent_id = intval($this->getJson($request, 'parent_id'));
        $category = [
            'name' => (string)$this->getJson($request, 'name'),
            'slug' => (string)$this->getJson($request, 'slug'),
        ];
        $category['parent_id'] = empty($parent_id) ? null : $parent_id;

        $form = $this->createFormByArray(MaterialsCategoryCreateType::class, $category);
        if ($form->isValid()) {
            $category = $materialsCategoriesServices->addCategory($user, $form);
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => true]);
    }

    /**
     * Редактирование категории материала
     *
     * @Route("/api/material/category", name="api_material_category_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="category_id", type="integer", description="ID категории", example="1"),
     *       @OA\Property(property="name", type="string", description="Название категории", example="Йога"),
     *       @OA\Property(property="parent_id", type="integer", description="ID родителя", example="2"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Категория успешно отредактирована")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Ошибка")
     *
     * @OA\Tag(name="admin materials categories")
     * @Security(name="Bearer")
     */
    public function categoryEditAction(
        Request $request,
        CoreSecurity $security,
        MaterialsCategoriesServices $materialsCategoriesServices
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsEditor() && !$user->getIsAdmin()) {
            return $this->jsonError(['roles' => "Запрещено, нет прав"], 403);
        }

        $parent_id = intval($this->getJson($request, 'parent_id'));
        $category_id = intval($this->getJson($request, 'category_id'));
        $category = [
            'name' => (string)$this->getJson($request, 'name'),
        ];
        $category['parent_id'] = empty($parent_id) ? null : $parent_id;
        $category['category_id'] = empty($category_id) ? null : $category_id;

        $form = $this->createFormByArray(MaterialsCategoryEditType::class, $category);
        if ($form->isValid()) {
            $category = $materialsCategoriesServices->editCategory($user, $form);
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => true]);
    }

    /**
     * Удаление дочерней категории материала
     *
     * @Route("/api/material/category/{id}",
     *      requirements={"id"="\d+"},
     *      name="api_material_category_delete",
     *      methods={"DELETE"}
     * )
     *
     * @OA\Parameter(name="id", in="path", description="ID дочерней категории",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Категория удалена")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Ошибка")
     *
     * @OA\Tag(name="admin materials categories")
     * @Security(name="Bearer")
     */
    public function categoryDeleteAction(
        Request $request,
        CoreSecurity $security,
        MaterialsCategoriesServices $materialsCategoriesServices,
        int $id
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsEditor() && !$user->getIsAdmin()) {
            return $this->jsonError(['roles' => "Запрещено, нет прав"], 403);
        }

        try {
            $materialsCategoriesServices->deleteCategory($user, $id);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()]);
        }

        return $this->jsonSuccess(['result' => true]);
    }

    /**
     * Список родительских категорий (для добавления категории)
     *
     * @Route(path="/api/material/category", name="api_material_category_get", methods={"GET"})
     *
     *
     * @OA\Response(response=200, description="Категории получены")
     * @OA\Response(response=401, description="Необходима авторизация")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin materials categories")
     * @Security(name="Bearer")
     */
    public function getParentCategory(
        CoreSecurity $security,
        Request $request,
        MaterialsCategoriesServices $materialsCategoriesServices
    ) {
        $user = $security->getUser();
        if (!$user->getIsEditor() && !$user->getIsAdmin()) {
            return $this->jsonError(['roles' => "Запрещено, нет прав"], 403);
        }
        return $this->jsonSuccess(['result' => $materialsCategoriesServices->getParentCategories()]);
    }

    /**
     * Список всех категорий
     *
     * @Route(path="/api/material/category/list", name="api_material_category_list", methods={"GET"})
     *
     *
     * @OA\Response(response=200, description="Категории получены")
     * @OA\Response(response=401, description="Необходима авторизация")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin materials categories")
     * @Security(name="Bearer")
     */
    public function getAllCategories(
        CoreSecurity $security,
        Request $request,
        MaterialsCategoriesServices $materialsCategoriesServices
    ) {
        $user = $security->getUser();
        if (!$user->getIsEditor() && !$user->getIsAdmin()) {
            return $this->jsonError(['roles' => "Запрещено, нет прав"], 403);
        }

        $order_by = ['sort_param' => "", "sort_type" => 'desc'];
        return $this->jsonSuccess([
            'result' => $materialsCategoriesServices->getAllCategories($order_by, "")
        ]);
    }

    /**
     * Список всех категорий для пользователя
     *
     * @Route(path="/api/material/categories", name="api_material_categories_user_list", methods={"GET"})
     *
     *
     * @OA\Response(response=200, description="Категории получены")
     * @OA\Response(response=401, description="Необходима авторизация")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="materials categories")
     * @Security(name="Bearer")
     */
    public function getUserAllCategories(
        CoreSecurity $security,
        Request $request,
        MaterialsCategoriesServices $materialsCategoriesServices
    ) {
        $user = $security->getUser();

        return $this->jsonSuccess(['result' => $materialsCategoriesServices->getUserAllCategories($user)]);
    }
}
