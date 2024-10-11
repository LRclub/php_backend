<?php

namespace App\Controller\Admin\Materials;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Services\Materials\MaterialsServices;

class AdminMaterialsController extends BaseApiController
{
    /**
     * Список материалов
     *
     * @Route("/admin/materials", name="admin_materials", methods={"GET"})
     *
     */
    public function materialsAction(
        CoreSecurity $security,
        Request $request,
        MaterialsServices $materialsServices,
        $category = null
    ): Response {
        $user = $security->getUser();
        $search = trim($request->query->get('search'));

        // Категория
        $category = trim($request->query->get('category'));
        $filter = $materialsServices->getMaterialsFilterData($request, $category);

        $materials = $materialsServices->getMaterials($user, $filter, false, false, true);
        $materials_count = (int)$materialsServices->getMaterials(
            $user,
            $filter,
            true,
            false,
            true
        );

        return $this->render('/pages/admin/materials/materials.html.twig', [
            'materials' => $materials,
            'materials_count' => $materials_count,
            'pages_count' => round($materials_count / $materialsServices::PAGE_OFFSET),
            'current_page' => $filter['page'],
            'search' => $search,
        ]);
    }

    /**
     * Создание материала
     *
     * @Route("/admin/materials/create", name="admin_material_create", methods={"GET"})
     */
    public function materialCreateAction(
        CoreSecurity $security,
        Request $request,
        MaterialsServices $materialsServices
    ): Response {
        return $this->render('/pages/admin/materials/create.html.twig');
    }

    /**
     * Редактирование/просмотр материала
     *
     * @Route("/admin/materials/{id}", requirements={"id"="\d+"}, name="admin_material_edit", methods={"GET"})
     */
    public function materialEditAction(
        CoreSecurity $security,
        Request $request,
        MaterialsServices $materialsServices,
        $id
    ): Response {
        $user = $security->getUser();
        $material = $materialsServices->getMaterialById($id, $user);
        if (!$material) {
            return $this->redirect('/');
        }

        return $this->render('/pages/admin/materials/edit.html.twig', [
            'id' => $id,
            'material' => $material
        ]);
    }
}
