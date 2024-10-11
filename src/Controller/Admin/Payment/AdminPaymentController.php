<?php

namespace App\Controller\Admin\Payment;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\Admin\AdminPaymentServices;

class AdminPaymentController extends BaseApiController
{
    /**
     * @Route("/admin/payment", name="admin_payment", methods={"GET"})
     */
    public function paymentAction(
        Request $request,
        AdminPaymentServices $adminPaymentServices
    ): Response {
        $search = trim($request->query->get('search'));
        $order_by['sort_param'] = mb_strtolower($request->query->get('sort'));
        $order = mb_strtolower($request->query->get('order'));
        $order_by['sort_type'] = ($order == self::SORT_DESC) ? self::SORT_DESC : self::SORT_ASC;
        $page = $this->getIntOrNull($request->query->get('page')) ?? 1;

        $payment_history = $adminPaymentServices->getPaymentHistory($page, $search, $order_by);

        return $this->render('/pages/admin/payment/list.html.twig', [
            'title' => "Все платежи",
            'result' => $payment_history['payment'],
            'pages' => $payment_history['pages'],
            'payments_count' => $payment_history['payments_count'],
            'page' => $page,
            'search' => $search
        ]);
    }
}
