<?php

namespace App\Controller;

use App\Controller\Base\BaseApiController;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\MailServices;
use App\Services\SMSServices;
use App\Services\Marketing\UTMServices;
use App\Services\User\TokenServices;
use App\Services\User\UserServices;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\UTM\UTMType;
use App\Services\QueueServices;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\Event\UserEventHistoryServices;

class SMSController extends BaseApiController
{
    private UserEventHistoryServices $userEventHistoryServices;

    public function __construct(
        UserEventHistoryServices $userEventHistoryServices
    ) {
        $this->userEventHistoryServices = $userEventHistoryServices;
    }
    /**
     * Отправка кода для авторизации пользователя
     *
     * @Route("/api/sms", name="api_code_send", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="phone",
     *                    type="string",
     *                    description="Номер",
     *                    example="+79888900000"
     * ),
     *       @OA\Property(property="role", type="string", description="Роль пользователя", example="user")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Код успешно отправлен")
     * @OA\Response(response=400, description="Ошибка при отправке")
     *
     * @OA\Tag(name="authorization")
     */
    public function sendAction(
        KernelInterface $kernel,
        Request $request,
        SMSServices $SMSServices,
        UserRepository $userRepository,
        QueueServices $queueServices
    ): Response {
        $phone = (string)$this->getJson($request, 'phone');
        $role = trim($this->getJson($request, 'role'));

        $formatted_phone = $SMSServices->phoneFormat($phone);
        $ip = $request->getClientIp();

        if (!$formatted_phone) {
            return $this->jsonError(['phone' => 'Введите корректный номер!']);
        }

        if (!isset(User::AVAILABLE_ROLES[$role])) {
            return $this->jsonError(['role' => 'Введите корректную роль!']);
        }

        $find_user = $userRepository->findByPhone($formatted_phone);

        if ($find_user && $find_user->getIsBlocked()) {
            return $this->jsonError(['is_blocked' => 'Пользователь заблокирован'], 403);
        }

        $is_available = $SMSServices->checkAvailability($ip, $formatted_phone);

        if (!$is_available) {
            return $this->jsonError(
                [
                    'phone' => 'Слишком большое количество отправок SMS, подождите!'
                ],
                403
            );
        }

        $authCode = $SMSServices->createCode($formatted_phone, $ip, $role);

        $result = [
            'id' => $authCode->getId(),
            'sms_status' => false,
            'send_email' => !empty($find_user) && $find_user->getIsConfirmed()
        ];

        //для демонстрации, без отправки СМС, сразу показываем пароль на фронте
        if ($kernel->getEnvironment() == 'dev') {
            $result['code'] = $authCode->getCode();
        } else {
            $result['sms_status'] = $SMSServices->sendSMS(
                $formatted_phone,
                'Код авторизации на сервисе - ' . $authCode->getCode()
            );
            if (!$result['sms_status']) {
                if ($find_user && $find_user->getEmail()) {
                    $queueServices->sendEmail(
                        $find_user->getEmail(),
                        $authCode->getCode() . ' - Код для авторизации на сервисе',
                        '/mail/user/restore/code.html.twig',
                        [
                            'code' => $authCode->getCode()
                        ]
                    );
                } else {
                    return $this->jsonError(['email' => 'Пользователь или email не найден'], 403);
                }
            }
        }

        return $this->jsonSuccess($result);
    }

    /**
     * Прием ввода кода пользователя
     *
     * @Route("/api/sms", name="api_code_validate", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="code_id", type="string", description="Идентификатор запроса", example="100"),
     *       @OA\Property(property="value", type="string", description="Код из SMS", example="101010"),
     *       @OA\Property(property="utm", type="array",
     *          example={
     *              {
     *                  "utm_source": "google",
     *                  "utm_medium": "cpc",
     *                  "utm_campaign": "instrumenti-moskva",
     *                  "utm_term": "балалайки",
     *                  "utm_content": "context",
     *                  "time": "1641325165"
     *              },
     *              {
     *                  "utm_source": "yandex",
     *                  "utm_medium": "cpc",
     *                  "utm_campaign": "balalaiki-moskva",
     *                  "utm_term": "купить балалайку в москве",
     *                  "utm_content": "search",
     *                  "time": "1642527272"
     *              }
     *          },
     *          @OA\Items(
     *              @OA\Property(property="utm_source",type="string",example="google"),
     *              @OA\Property(property="utm_medium",type="string",example="cpc"),
     *              @OA\Property(property="utm_campaign",type="string",example="instrumenti-moskva"),
     *              @OA\Property(property="utm_term",type="string",example="балалайки"),
     *              @OA\Property(property="utm_content",type="string",example="context"),
     *              @OA\Property(property="time",type="string",example="1641325165")
     *          )
     *       )
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Пользователь успешно создан")
     * @OA\Response(response=400, description="Ошибка при создании")
     *
     * @OA\Tag(name="authorization")
     */
    public function checkAction(
        Request $request,
        SMSServices $SMSServices,
        TokenServices $tokenServices,
        UserRepository $userRepository,
        UserServices $userServices,
        ParameterBagInterface $params,
        QueueServices $queueServices,
        UTMServices $UTMServices
    ): Response {
        $code_id = (int)$this->getJson($request, 'code_id');
        $value = trim($this->getJson($request, 'value'));

        $auth_code = $SMSServices->getCodeItem($code_id);

        $validate = $SMSServices->codeValidation($auth_code);
        if ($validate !== null) {
            return $this->jsonError($validate['error'], $validate['status']);
        }

        if ($auth_code->getCode() != $value) {
            $SMSServices->increaseAttempts($auth_code);
            return $this->jsonError(['code_id' => 'Код введен неверно!'], 403);
        }

        $SMSServices->markCompleted($auth_code);

        $user = $userRepository->findByPhone($auth_code->getPhone());

        if (!$user) {
            $user = $userServices->createUser($auth_code);
            $this->userEventHistoryServices->saveUserEvent($user, 'registration');
            $notifications = $params->get('mail.notifications');

            if (!empty($notifications)) {
                foreach ($notifications as $notify_mail) {
                    $queueServices->sendEmail(
                        $notify_mail,
                        'Регистрация нового пользователя на сервисе',
                        '/mail/admin/registration.html.twig',
                        [
                            'phone' => $auth_code->getPhone(),
                            'id' => $user->getId(),
                            'role' => $auth_code->getRole()
                        ]
                    );
                }
            }
        }

        $utm = $UTMServices->filterBrokenTags((array) $this->getJson($request, 'utm'));
        if ($utm) {
            $form = $this->createFormByArray(UTMType::class, ['utm' => $utm]);
            if ($form->isValid()) {
                try {
                    $UTMServices->registrationTags($user, $form);
                } catch (LogicException $e) {
                }
            }
        }

        $result = $tokenServices->createPair($user);

        $result['is_empty_data'] = $user->getIsEmptyProfile();

        return $this->jsonSuccess($result);
    }

    /**
     * Отправка кода для авторизации пользователя путем email
     *
     * @Route("/api/email/{id}", requirements={"id"="\d+"}, name="api_email_send", methods={"POST"})
     *
     * @OA\Parameter(name="id", in="path", description="code ID",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Код успешно отправлен")
     * @OA\Response(response=400, description="Ошибка при совершении звонка")
     * @OA\Response(response=404, description="Идентификатор авторизации не найден")
     * @OA\Response(response=403, description="Проблема с кодом")
     *
     * @OA\Tag(name="authorization")
     */
    public function emailAction(
        Request $request,
        SMSServices $SMSServices,
        QueueServices $queueServices,
        UserRepository $userRepository,
        int $id
    ): Response {
        $auth_code = $SMSServices->getCodeItem($id);

        $validate = $SMSServices->codeValidation($auth_code);
        if ($validate !== null) {
            return $this->jsonError($validate['error'], $validate['status']);
        }

        if ($auth_code->getIsMailed()) {
            return $this->jsonError(['id' => 'Email уже отправлен'], 403);
        }

        $find_user = $userRepository->findByPhone($auth_code->getPhone());
        if ($find_user && $find_user->getEmail()) {
            $queueServices->sendEmail(
                $find_user->getEmail(),
                $auth_code->getCode() . ' - Код для авторизации',
                '/mail/user/restore/code.html.twig',
                [
                    'code' => $auth_code->getCode()
                ]
            );
        } else {
            return $this->jsonError(['email' => 'Email или пользователь не найден'], 403);
        }

        $SMSServices->setMailed($auth_code);

        return $this->jsonSuccess();
    }
}
