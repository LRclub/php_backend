<?php

namespace App\Controller\Payment;

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

class PaymentController extends BaseApiController
{
    /**
     * Проверка параметров в скрипте завершения операции (SuccessURL)
     *
     * @Route("/payment/success", name="payment_success", methods={"GET"})
     *
     * @OA\Response(response=200, description="Платеж успешно прошел")
     *
     * @OA\Tag(name="payment")
     */
    public function successPay(CoreSecurity $security, Request $request, PaymentServices $paymentServices)
    {
        /*$params = $request->query->all();

        try {
            $paymentServices->successPayment($params);
        } catch (LogicException $e) {
            return $this->redirectToRoute('payment_error');
        }*/

        return $this->render('/pages/payment/success.html.twig', [
            'title' => 'Информация об оплате'
        ]);
    }

    /**
     * Получение уведомления об отмене операции (ErrorURL)
     *
     * @Route("/payment/error", name="payment_error", methods={"GET"})
     *
     * @OA\Response(response=200, description="Платеж отменен")
     *
     * @OA\Tag(name="payment")
     * @Security(name="Bearer")
     */
    public function errorPay(CoreSecurity $security, Request $request, PaymentServices $paymentServices)
    {
        return $this->render('/pages/payment/error.html.twig', [
            'title' => 'Информация об оплате'
        ]);
    }

    /**
     * Страница создания оплаты
     *
     * @Route("/panel/payment", name="payment_create", methods={"GET"})
     *
     */
    public function payment()
    {
        return $this->render('/pages/payment/create.html.twig', [
            'title' => 'Оплата'
        ]);
    }
}
