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
use App\Services\Materials\MaterialsFavoriteServices;
use App\Services\Materials\MaterialsServices;

class MaterialsFavoritesController extends BaseApiController
{
    /**
     * Добавление материала в избранное
     *
     * @Route("/api/material/favorite", name="api_material_favorite_add", methods={"PUT"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="material_id", type="integer", description="ID материала", example="1"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Успешно добавлен в избранное")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=404, description="Материал не найден")
     *
     * @OA\Tag(name="materials favorite")
     * @Security(name="Bearer")
     */
    public function materialAddAction(
        Request $request,
        CoreSecurity $security,
        MaterialsFavoriteServices $materialsFavoriteServices
    ): Response {
        $user = $security->getUser();
        $material_id = (int)$this->getJson($request, 'material_id');

        try {
            $materialsFavoriteServices->addMaterial($user, $material_id);
        } catch (LogicException $e) {
            return $this->jsonError(['material_id' => $e->getMessage()], 404);
        }

        return $this->jsonSuccess(['result' => true]);
    }

    /**
     * Удаление материала из избранного
     *
     * @Route("/api/material/favorite/{id}", name="api_material_favorite_remove", methods={"DELETE"})
     *
     * @OA\Parameter(name="id", in="path", description="ID материала",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Успешно удален из избранного")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="materials favorite")
     * @Security(name="Bearer")
     */
    public function materialDeleteAction(
        Request $request,
        CoreSecurity $security,
        MaterialsFavoriteServices $materialsFavoriteServices,
        int $id
    ): Response {
        $user = $security->getUser();

        try {
            $materialsFavoriteServices->deleteMaterial($user, $id);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()], 404);
        }

        return $this->jsonSuccess(['result' => true]);
    }

    /**
     * Получение материалов в избранном
     *
     * @Route("/api/materials/favorite", name="api_materials_favorite_list", methods={"GET"})
     *
     * @OA\Parameter(
     *   in="query", name="category", schema={"type"="string", "example"="yoga"}, description="Slug категории"
     *  ),
     * @OA\Parameter(
     *  in="query", name="page", schema={"type"="integer", "example"=1}, description="Номер страниц (По умолчанию 1)"
     *  ),
     * @OA\Parameter(
     *  in="query", name="sort", schema={"type"="string", "example"="views_count"}, description="Параметр сортировки"
     *  )
     * @OA\Parameter(
     *  in="query", name="order", schema={"type"="string", "example"="desc"}, description="Тип сортировки"
     *  )
     * @OA\Parameter(
     *  in="query", name="type", schema={"type"="string", "example"="stream"}, description="Тип материала"
     *  )
     *
     * @OA\Response(response=200, description="Информация получена")
     *
     * @OA\Tag(name="materials favorite")
     * @Security(name="Bearer")
     */
    public function getMaterialsFavoriteAction(
        Request $request,
        CoreSecurity $security,
        MaterialsServices $materialsServices
    ): Response {
        $user = $security->getUser();

        // Категория
        $category = trim($request->query->get('category'));

        $filter = $materialsServices->getMaterialsFilterData($request, $category);

        $materials = $materialsServices->getMaterials($user, $filter, false, true);
        $materials_count = (int)$materialsServices->getMaterials(
            $user,
            $filter,
            true,
            true
        );

        return $this->jsonSuccess([
            'result' => $materials,
            'category' => $category,
            'materials_count' => $materials_count,
            'current_page' => $filter['page'],
            'pages_count' => round($materials_count / $materialsServices::PAGE_OFFSET)
        ]);
    }
}
