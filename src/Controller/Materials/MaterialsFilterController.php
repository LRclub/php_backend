<?php

namespace App\Controller\Materials;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\Materials\MaterialsServices;

class MaterialsFilterController extends BaseApiController
{
    /**
     * Страница поиска
     *
     * @Route("/panel/search", name="panel_search", methods={"GET"})
     */
    public function searchAction(
        CoreSecurity $security,
        Request $request,
        MaterialsServices $materialsServices
    ): Response {
        $user = $security->getUser();

        $search = mb_strtolower($request->query->get('search'));

        $result = $materialsServices->filterMaterials($user, $search);

        return $this->render('/pages/materials/search.html.twig', [
            'result' => $result
        ]);
    }
}
