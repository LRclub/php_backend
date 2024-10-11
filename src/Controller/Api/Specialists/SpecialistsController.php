<?php

namespace App\Controller\Api\Specialists;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\Payment\PaymentServices;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\Admin\AdminSpecialistsServices;
use App\Services\Admin\AdminSpecialistsCategoriesServices;

class SpecialistsController extends BaseApiController
{
    /**
     * Список специалистов для пользователя
     *
     * @Route("/api/specialists", name="api_specialists", methods={"GET"})
     *
     * @OA\Parameter(in="query", name="category_id",
     *               schema={"type"="string", "example"=""},
     *               description="Идентификатор категории специалистов, необязательный параметр"
     * ),
     * @OA\Parameter(in="query", name="field",
     *               schema={"type"="string", "example"="sort"},
     *               description="Поле по которому сортируем (price, sort)"
     * ),
     * @OA\Parameter(in="query", name="order",
     *               schema={"type"="string", "example"="desc"},
     *               description="Поле по которому сортируем (desc, asc)"
     * )
     *
     * @OA\Tag(name="specialists")
     * @Security(name="Bearer")
     */
    public function specialistsAction(
        Request $request,
        AdminSpecialistsServices $adminSpecialistsServices
    ) {
        $order_by = [];

        // Категория
        $category_id = intval($request->query->get('category_id'));

        //порядок сортировки
        $order = mb_strtolower($request->query->get('order'));
        $order_by['sort_param'] = mb_strtolower($request->query->get('field'));
        $order_by['sort_type'] = ($order == self::SORT_DESC) ? self::SORT_DESC : self::SORT_ASC;

        $specialists = $adminSpecialistsServices->getSpecialists($category_id, $order_by);

        return $this->jsonSuccess([
            'specialists' => $specialists,
            'category_id' => $category_id,
            'field' => $order_by['sort_param'],
            'order' => $order_by['sort_type']
        ]);
    }


    /**
     * Список категорий
     *
     * @Route("/api/specialists/categories", name="api_specialists_categories", methods={"GET"})
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="specialists")
     * @Security(name="Bearer")
     */
    public function getSpecialistsCategories(
        AdminSpecialistsCategoriesServices $adminSpecialistsCategoriesServices
    ): Response {
        $result = $adminSpecialistsCategoriesServices->getCategories();

        return $this->jsonSuccess(['result' => $result]);
    }
}
