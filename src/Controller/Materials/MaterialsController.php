<?php

namespace App\Controller\Materials;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\Materials\MaterialsServices;

class MaterialsController extends BaseApiController
{
    /**
     * Список материалов
     *
     * @Route("/panel/materials/{category?}", name="materials", methods={"GET"})
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

        $materials = $materialsServices->getMaterials($user, $filter);
        $materials_count = (int)$materialsServices->getMaterials(
            $user,
            $filter,
            true
        );

        return $this->render('/pages/materials/list.html.twig', [
            'materials' => $materials,
            'category' => $category,
            'materials_count' => $materials_count,
            'current_page' => $filter['page'],
            'pages_count' => round($materials_count / $materialsServices::PAGE_OFFSET),
            'sort_param' => $filter['sort_param'],
            'sort_type' => $filter['sort_type'],
            'category_slug' => $filter['category_slug'],
            'page' => $filter['page'],
            'type' => $filter['type'],
            'search' => $filter['search']
        ]);
    }

    /**
     * Просмотр материала
     *
     * @Route("/panel/material/{id}", requirements={"id"="\d+"}, name="material_view", methods={"GET"})
     */
    public function materialAction(
        CoreSecurity $security,
        Request $request,
        MaterialsServices $materialsServices,
        $id
    ): Response {
        $user = $security->getUser();
        $material = $materialsServices->getMaterialById($id, $user);
        if (!$material) {
            throw new NotFoundHttpException();
        }

        $materialsServices->markViewed($id);
        return $this->render('/pages/materials/view.html.twig', [
            'material' => $material,
            'comments_collector_id' => $material['comments_collector_id']
        ]);
    }
}
