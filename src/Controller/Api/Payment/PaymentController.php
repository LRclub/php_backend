<?php

namespace App\Controller\Api\Payment;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Repository\TariffsRepository;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\Payment\PaymentServices;
use App\Services\Payment\SubscriptionHistoryServices;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\Payment\TariffsServices;
use App\Services\Marketing\PromocodeServices;
use App\Form\Payment\SpecialistPaymentType;

class PaymentController extends BaseApiController
{
    /**
     * Получение информации о тарифах
     *
     * @Route("/api/tariffs", name="api_get_tariffs", methods={"GET"})
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     *
     * @OA\Tag(name="payment")
     */
    public function getTariffs(
        TariffsServices $tariffsServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        $tariffs = $tariffsServices->getTariffs($user);

        return $this->jsonSuccess(['result' => $tariffs]);
    }

    /**
     * Получение информации об оплатах
     *
     * @Route("/api/payment", name="api_payment_history", methods={"GET"})
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     *
     * @OA\Tag(name="payment")
     * @Security(name="Bearer")
     */
    public function getPaymentHistory(
        CoreSecurity $security,
        SubscriptionHistoryServices $subscriptionHistoryServices
    ) {
        $user = $security->getUser();
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $result = $subscriptionHistoryServices->getSubscriptionHistory($user);

        return $this->jsonSuccess(['result' => $result]);
    }

    /**
     * Создать оплату подписки
     *
     * @Route("/api/invoice", name="api_invoice_link", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="tariff_id", type="integer", description="ID тарифа", example="1"),
     *       @OA\Property(property="promocode", type="string", description="Промокод", example="PAYMENT"),
     *     ),
     *   )
     * )
     *
     * @OA\Response(response=200, description="Ссылка на оплату получена")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=401, description="Необходима авторизация")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     *
     * @OA\Tag(name="payment")
     * @Security(name="Bearer")
     */
    public function createPayment(Request $request, CoreSecurity $security, PaymentServices $paymentServices)
    {
        $user = $security->getUser();

        $tariff_id = (int)$this->getJson($request, 'tariff_id');
        $promocode = (string)$this->getJson($request, 'promocode');

        try {
            $link = $paymentServices->createInvoice($user, $tariff_id, $promocode);
        } catch (LogicException $e) {
            return $this->jsonError(['#' => $e->getMessage()]);
        }
        return $this->jsonSuccess(['result' => $link]);
    }

    /**
     * Удалить подписку на рекуррентный платеж
     *
     * @Route("/api/payment/{id}",
     *      requirements={"id"="\d+"},
     *      name="api_payment_recurrent",
     *      methods={"DELETE"}
     *    )
     *
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID invoice",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Автосписание успешно отменено")
     * @OA\Response(response=400, description="Оплата не найдена")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="payment")
     * @Security(name="Bearer")
     */
    public function deletePaymentRecurrent(
        Request $request,
        PaymentServices $paymentServices,
        CoreSecurity $security,
        int $id
    ) {
        $user = $security->getUser();

        try {
            $invoice = $paymentServices->cancelAutoPayment($user, $id);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()], 400);
        }

        return $this->jsonSuccess(['result' => ['id' => $invoice->getId()]]);
    }

    /**
     * Создать оплату консультации специалиста
     *
     * @Route("/api/invoice/specialist", name="api_invoice_specialist_link", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="specialist_id", type="integer", description="ID специалиста", example="1"),
     *       @OA\Property(property="fio", type="string", description="ФИО", example="Иванов Иван Иванович"),
     *       @OA\Property(property="email", type="string", description="E-mail специалиста", example="email@test.ru"),
     *       @OA\Property(property="phone", type="string", description="Номер", example="+79888900000"),
     *       @OA\Property(property="comment", type="string", description="Комментарий", example="Нужна помощь"),
     *     ),
     *   )
     * )
     *
     * @OA\Response(response=200, description="Ссылка на оплату получена")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=401, description="Необходима авторизация")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     *
     * @OA\Tag(name="payment")
     * @Security(name="Bearer")
     */
    public function createSpecialistPayment(Request $request, CoreSecurity $security, PaymentServices $paymentServices)
    {
        $user = $security->getUser();

        $payment['specialist'] = $this->getIntOrNull($this->getJson($request, 'specialist_id'));
        $payment['fio'] = (string)$this->getJson($request, 'fio');
        $payment['email'] = (string)$this->getJson($request, 'email');
        $payment['phone'] = (string)$this->getJson($request, 'phone');
        $payment['comment'] = (string)$this->getJson($request, 'comment');

        $form = $this->createFormByArray(SpecialistPaymentType::class, $payment);

        if ($form->isValid()) {
            try {
                $link = $paymentServices->createSpecialistInvoice($user, $form);
            } catch (LogicException $e) {
                return $this->jsonError(["#" => $e->getMessage()], 400);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => $link]);
    }

    /**
     * Получение уведомления об исполнении операции (ResultURL)
     *
     * @Route("/api/robokassa/result", name="api_robokassa_result", methods={"GET"})
     *
     * @OA\Response(response=200, description="Платеж успешно прошел")
     *
     * @OA\Tag(name="payment")
     * @Security(name="Bearer")
     */
    public function resultPay(Request $request, PaymentServices $paymentServices)
    {
        $params = $request->query->all();
        try {
            $paymentServices->resultPayment($params);
        } catch (LogicException $e) {
            echo "bad sign\n";
            exit();
        }
    }

    /**
     * Информация о пересчете
     *
     * @Route("/api/user/recosting", name="api_user_recosting", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="tariff_id", type="integer", description="ID тарифа", example="1"),
     *       @OA\Property(property="promocode", type="string", description="Промокод", example="PAYMENT"),
     *     ),
     *   )
     * )
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=401, description="Необходима авторизация")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     *
     * @OA\Tag(name="payment")
     * @Security(name="Bearer")
     */
    public function recosting(
        Request $request,
        CoreSecurity $security,
        PaymentServices $paymentServices,
        PromocodeServices $promocodeServices
    ) {
        $user = $security->getUser();
        $tariff_id = $this->getIntOrNull($this->getJson($request, 'tariff_id'));
        $promocode = (string)$this->getJson($request, 'promocode');

        if (!empty($promocode)) {
            try {
                $promocodeServices->validate($user, $promocodeServices->getPromoByCode($promocode));
            } catch (LogicException $e) {
                return $this->jsonError(['promocode' => $e->getMessage()]);
            }
        }

        try {
            $result = $paymentServices->userRecostingInfo($user, $tariff_id, $promocode);
        } catch (LogicException $e) {
            return $this->jsonError(['tariff_id' => $e->getMessage()]);
        }

        return $this->jsonSuccess(['result' => $result]);
    }
}
