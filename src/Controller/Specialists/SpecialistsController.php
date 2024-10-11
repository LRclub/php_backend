<?php

namespace App\Controller\Specialists;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/panel/specialists/{category?}", name="panel_specialists", methods={"GET"})
     *
     */
    public function specialistsAction(
        CoreSecurity $security,
        Request $request,
        AdminSpecialistsServices $adminSpecialistsServices,
        AdminSpecialistsCategoriesServices $adminSpecialistsCategoriesServices,
        $category
    ) {
        // Сортировка
        $order_by['sort_param'] = mb_strtolower($request->query->get('sort'));
        $order = mb_strtolower($request->query->get('order'));
        $order_by['sort_type'] = ($order == self::SORT_DESC) ? self::SORT_DESC : self::SORT_ASC;

        $specialists = $adminSpecialistsServices->getSpecialists($category, $order_by);
        $categories = $adminSpecialistsCategoriesServices->getCategories();

        return $this->render('/pages/specialists/list.html.twig', [
            'specialists' => $specialists,
            'categories' => $categories,
            'category_id' => $category,
            'sort' => $order_by['sort_param'],
            'order' => $order_by['sort_type']
        ]);
    }
}
