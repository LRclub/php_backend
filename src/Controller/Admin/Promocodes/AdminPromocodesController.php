<?php

namespace App\Controller\Admin\Promocodes;

use App\Entity\Seo;
use App\Form\SeoType;
use App\Repository\SeoRepository;
use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\Marketing\PromocodeServices;

class AdminPromocodesController extends BaseApiController
{
    /**
     * @Route("/admin/promocodes", name="admin_promocodes_index", methods={"GET"})
     */
    public function index(
        PromocodeServices $promocodeServices,
        Request $request
    ): Response {
        $search = trim($request->query->get('search'));
        $order_by['sort_param'] = mb_strtolower($request->query->get('sort'));
        $order = mb_strtolower($request->query->get('order'));
        $order_by['sort_type'] = ($order == self::SORT_ASC) ? self::SORT_ASC : self::SORT_DESC;

        $promocodes = $promocodeServices->getPromocodes($order_by, $search);

        return $this->render('/pages/admin/promocodes/list.html.twig', [
            'promocodes' => $promocodes
        ]);
    }

    /**
     * @Route("/admin/promocodes/create", name="admin_promocodes_create", methods={"GET"})
     */
    public function show(): Response
    {
        return $this->render('/pages/admin/promocodes/create.html.twig', []);
    }
}
