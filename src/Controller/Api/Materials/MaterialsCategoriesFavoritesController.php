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
use App\Services\Materials\MaterialsCategoriesFavoritesServices;

class MaterialsCategoriesFavoritesController extends BaseApiController
{
    /**
     * Добавление категории в избранное
     *
     * @Route("/api/material/category/favorite", name="api_material_category_favorite_add", methods={"PUT"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="category_id", type="integer", description="ID материала", example="1"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Успешно добавлен в избранное")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Материал не найден")
     *
     * @OA\Tag(name="category favorite")
     * @Security(name="Bearer")
     */
    public function materialAddAction(
        Request $request,
        CoreSecurity $security,
        MaterialsCategoriesFavoritesServices $materialsCategoriesFavoritesServices
    ): Response {
        $user = $security->getUser();
        $category_id = (int)$this->getJson($request, 'category_id');

        try {
            $materialsCategoriesFavoritesServices->addCategory($user, $category_id);
        } catch (LogicException $e) {
            return $this->jsonError(['category_id' => $e->getMessage()], 404);
        }

        return $this->jsonSuccess(['result' => true]);
    }

    /**
     * Удаление категории из избранного
     *
     * @Route("/api/material/category/favorite/{id}",
     *      requirements={"id"="\d+"},
     *      name="api_material_category_favorite_remove",
     *      methods={"DELETE"}
     *    )
     *
     * @OA\Parameter(name="id", in="path", description="ID категории",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Успешно удален из избранного")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="category favorite")
     * @Security(name="Bearer")
     */
    public function materialDeleteAction(
        Request $request,
        CoreSecurity $security,
        MaterialsCategoriesFavoritesServices $materialsCategoriesFavoritesServices,
        int $id
    ): Response {
        $user = $security->getUser();

        try {
            $materialsCategoriesFavoritesServices->deleteCategory($user, $id);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()], 404);
        }

        return $this->jsonSuccess(['result' => true]);
    }

    /**
     * Получение категорий в избранном
     *
     * @Route("/api/materials/category/favorite", name="api_materials_category_favorite_list", methods={"GET"})
     *
     * @OA\Response(response=200, description="Информация получена")
     *
     * @OA\Tag(name="category favorite")
     * @Security(name="Bearer")
     */
    public function getCategoriesFavoriteAction(
        Request $request,
        CoreSecurity $security,
        MaterialsCategoriesFavoritesServices $materialsCategoriesFavoritesServices
    ): Response {
        $user = $security->getUser();
        $result = [];
        try {
            $result = $materialsCategoriesFavoritesServices->getFavoriteCategories($user);
        } catch (LogicException $e) {
            return $this->jsonError(['error' => $e->getMessage()], 404);
        }

        return $this->jsonSuccess(['result' => $result]);
    }
}
