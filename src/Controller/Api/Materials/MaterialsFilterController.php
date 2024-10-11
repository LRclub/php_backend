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
use App\Services\Admin\AdminMaterialsServices;
use App\Form\Materials\MaterialsCreateType;
use App\Services\Materials\MaterialsServices;

class MaterialsFilterController extends BaseApiController
{
    /**
     * Поиск материалов (фильтр)
     *
     * @Route("/api/filter", name="api_materials_filter", methods={"GET"})
     *
     * @OA\Parameter(
     *              in="query", name="search",
     *               schema={"type"="string", "example"="1"},
     *              description="id/заголовок/описание"
     *              )
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=401, description="Необходима авторизация")
     *
     * @OA\Tag(name="filter")
     * @Security(name="Bearer")
     */
    public function materialsFilterAction(
        Request $request,
        CoreSecurity $security,
        MaterialsServices $materialsServices
    ): Response {
        $user = $security->getUser();

        $search = mb_strtolower($request->query->get('search'));

        $result = $materialsServices->filterMaterials($user, $search);

        return $this->jsonSuccess(['result' => $result]);
    }
}
