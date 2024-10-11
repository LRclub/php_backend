<?php

namespace App\Controller\Materials;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Services\Materials\MaterialsServices;

class MaterialsFavoritesController extends BaseApiController
{
    /**
     * Список материалов в избранном
     *
     * @Route("/panel/favorite/{category?}", name="panel_materials_favorite", methods={"GET"})
     *
     */
    public function materialsAction(
        CoreSecurity $security,
        Request $request,
        MaterialsServices $materialsServices,
        $category = null
    ): Response {
        $user = $security->getUser();

        $filter = $materialsServices->getMaterialsFilterData($request, $category);

        $materials = $materialsServices->getMaterials($user, $filter, false, true);
        $materials_count = (int)$materialsServices->getMaterials(
            $user,
            $filter,
            true,
            true
        );

        return $this->render('/pages/materials/list.html.twig', [
            'is_favorites' => true,
            'materials' => $materials,
            'category' => $category,
            'materials_count' => $materials_count,
            'current_page' => $filter['page'],
            'pages_count' => round($materials_count / $materialsServices::PAGE_OFFSET),
            'sort_param' => $filter['sort_param'],
            'sort_type' => $filter['sort_type'],
            'type' => $filter['type']
        ]);
    }
}
