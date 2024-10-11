<?php

namespace App\Controller\Admin\Materials;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\Materials\MaterialsCategoriesServices;

class AdminMaterialsCategoriesController extends BaseApiController
{
    /**
     * Список категорий материалов
     *
     * @Route("/admin/materials/categories", name="admin_materials_categories", methods={"GET"})
     *
     */
    public function materialsCategoriesAction(
        CoreSecurity $security,
        Request $request,
        MaterialsCategoriesServices $materialsCategoriesServices
    ): Response {
        $user = $security->getUser();

        $search = trim($request->query->get('search'));
        $order_by['sort_param'] = mb_strtolower($request->query->get('sort'));
        $order = mb_strtolower($request->query->get('order'));
        $order_by['sort_type'] = ($order == self::SORT_ASC) ? self::SORT_ASC : self::SORT_DESC;

        $categories = $materialsCategoriesServices->getAllCategories($order_by, $search);

        return $this->render('/pages/admin/materials/categories.html.twig', [
            'title' => 'Категории материалов',
            'categories' => $categories
        ]);
    }

    /**
     * Редактировать категорию материала
     *
     * @Route(
     *      "/admin/materials/category/{id}",
     *      requirements={"id"="\d+"},
     *      name="admin_materials_category_edit",
     *      methods={"GET"}
     * )
     *
     */
    public function materialsCategoriesViewAction(
        CoreSecurity $security,
        Request $request,
        MaterialsCategoriesServices $materialsCategoriesServices,
        $id
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsEditor() && !$user->getIsAdmin()) {
            return $this->redirect('/');
        }
        $category = $materialsCategoriesServices->getCategoryById($id);

        if (!$category) {
            throw new NotFoundHttpException();
        }

        return $this->render('/pages/admin/materials/category_edit.html.twig', [
            'title' => 'Редактировать категорию материалов',
            'category' => $category
        ]);
    }

    /**
     * Редактировать категорию материала
     *
     * @Route("/admin/materials/category/create", name="admin_materials_category_create", methods={"GET"})
     *
     */
    public function materialsCategoriesCreateAction(
        CoreSecurity $security,
        Request $request,
        MaterialsCategoriesServices $materialsCategoriesServices
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsEditor() && !$user->getIsAdmin()) {
            return $this->redirect('/');
        }

        return $this->render('/pages/admin/materials/category_create.html.twig', [
            'title' => 'Создать категорию материалов'
        ]);
    }
}
