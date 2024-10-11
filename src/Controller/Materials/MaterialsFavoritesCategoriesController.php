<?php

namespace App\Controller\Materials;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\Materials\MaterialsCategoriesFavoritesServices;

class MaterialsFavoritesCategoriesController extends BaseApiController
{
    /**
     * Список категорий материалов в избранном
     *
     * @Route("/panel/categories/favorite", name="panel_materials_categories_favorite", methods={"GET"})
     *
     */
    public function materialsFavoritesCategoriesAction(
        CoreSecurity $security,
        Request $request,
        MaterialsCategoriesFavoritesServices $materialsCategoriesFavoritesServices
    ): Response {
        $user = $security->getUser();
        $categories = $materialsCategoriesFavoritesServices->getFavoriteCategories($user);

        return $this->render('/pages/materials/favorites_categories.html.twig', [
            'categories' => $categories
        ]);
    }
}
