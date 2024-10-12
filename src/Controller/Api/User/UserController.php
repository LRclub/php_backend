<?php

namespace App\Controller\Api\User;

use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use App\Controller\Base\BaseApiController;
// Components
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
// Services
use App\Services\User\UserServices;
use App\Services\SMSServices;
use App\Services\User\EmailConfirmationServices;
use App\Services\Event\UserEventHistoryServices;
use App\Services\Marketing\PromocodeServices;
use App\Services\Marketing\UTMServices;
use App\Services\QueueServices;
use App\Services\Marketing\PromocodeAction\PromocodeActionFactory;
// Repository
use App\Repository\UserRepository;
use App\Repository\PromocodesRepository;
// Form
use App\Form\User\UpdateUserType;
use App\Form\User\AdminUpdateUserType;
use App\Form\User\InitUserDataType;
use App\Form\UTM\UTMType;

class UserController extends BaseApiController
{
    private ParameterBagInterface $params;
    private UserEventHistoryServices $userEventHistoryServices;

    public function __construct(
        ParameterBagInterface $params,
        UserEventHistoryServices $userEventHistoryServices
    ) {
        $this->params = $params;
        $this->userEventHistoryServices = $userEventHistoryServices;
    }

   
      
       @Route("/api/user", name="api_user_update_data", methods={"PATCH"}))
      
       @OA\RequestBody(
         @OA\MediaType(
           mediaType="application/json",
           @OA\Schema(
             type="object",
             @OA\Property(property="admin_roles",
                type="array",
                description="Роль пользователя",
                example={"ROLE_ADMIN","ROLE_EDITOR", "ROLE_MODERATOR"},
                @OA\Items(type="string")
             ),
             @OA\Property(property="admin_user_id", type="integer", description="id пользователя", example="2"),
             @OA\Property(property="admin_is_blocked", type="boolean", description="Блокировка", example="true"),
             @OA\Property(property="first_name", type="string", description="Имя", example="Иван"),
             @OA\Property(property="last_name", type="string", description="Фамилия", example="Иванов"),
             @OA\Property(property="patronymic_name", type="string", description="Отчество", example="Иванович"),
             @OA\Property(property="phone", type="string", description="Номер телефона", example="+78800553535"),
             @OA\Property(property="email", type="string", description="Email", example="ivanov@mail.ru"),
             @OA\Property(property="birthday", type="string", description="Дата рождения", example="1980-10-30"),
             @OA\Property(property="avatar_id", type="integer", description="ID изображения", example="1"),
      
             @OA\Property(property="country_id", type="string", description="Код страны", example="179"),
             @OA\Property(property="city", type="string", description="Город", example="Москва"),
             @OA\Property(property="vk", type="string", description="Ссылка на профиль Вконтакте", example=""),
             @OA\Property(property="telegram", type="string", description="Ссылка на профиль Телеграм", example=""),
             @OA\Property(property="instagram", type="string", description="Ссылка на профиль Инстаграмм", example=""),
             @OA\Property(property="ok", type="string", description="Ссылка на профиль Одноклассники", example=""),
      
             @OA\Property(property="super_power", type="string", description="Ваша супер-сила", example=""),
             @OA\Property(property="principles", type="string", description="Ваши ценности/принципы", example=""),
             @OA\Property(property="description", type="string", description="Чем занимаетесь, ниша, ваш опыт?",
           example="Работаю в сфере красоты"),
             @OA\Property(property="interests", type="string", description="Ваши увлечения",
           example="Увлекаюсь йогой и питанием"),
      
             @OA\Property(
               property="email_new_materials",
               type="boolval",
               description="Email новые материалы",
               example="true"
             ),
             @OA\Property(
               property="email_subscription_history",
               type="boolval",
               description="Email подписка",
               example="true"
             ),
             @OA\Property(
               property="email_notice",
               type="boolval",
               description="Email уведомления",
               example="true"
             ),
           )
         )
       )
      
       @OA\Response(response=200, description="Информация обновлена")
       @OA\Response(response=401, description="Необходима авторизация")
      
       @OA\Tag(name="user")
       @Security(name="Bearer")
   
    public function userUpdateAction(
        Request $request,
        CoreSecurity $security,
        EmailConfirmationServices $emailConfirmationServices,
        QueueServices $queueServices,
        UserServices $userServices,
        SMSServices $SMSServices,
        UserRepository $userRepository
    ): Response {
        $user = $security->getUser();
        $user_data = [];
        $email_is_changed = false;
        $old_email = $user->getEmail();
        $is_admin = false;
        $roles = $user->getRoles();

        if ($user->getIsAdmin()) {
            $user_data['user_id'] = (int)$this->getJson($request, 'admin_user_id');
            if (!empty($user_data['user_id'])) {
                $user = $userRepository->find($user_data['user_id']);
                $roles = (array)$this->getJson($request, 'admin_roles');
                $user_data['admin_is_blocked'] = (bool)$this->getJson($request, 'admin_is_blocked');
                $is_admin = true;
                if (!$user) {
                    return $this->jsonError(['user_id' => "Пользователь не существует"]);
                }
            }
        }

        $email = (string)$this->getJson($request, 'email');
        $user_data['first_name'] = (string)$this->getJson($request, 'first_name');
        $user_data['last_name'] = (string)$this->getJson($request, 'last_name');
        $user_data['patronymic_name'] = (string)$this->getJson($request, 'patronymic_name');
        $user_data['phone'] = $SMSServices->phoneFormat((string)$this->getJson($request, 'phone'));
        $user_data['email'] = $email;
        $user_data['birthday'] = (string)$this->getJson($request, 'birthday');
        $user_data['avatar'] = $this->getJson($request, 'avatar_id');

        $user_data['email_new_materials'] = (bool)$this->getJson($request, 'email_new_materials');
        $user_data['email_subscription_history'] = (bool)$this->getJson($request, 'email_subscription_history');
        $user_data['email_notice'] = (bool)$this->getJson($request, 'email_notice');

        $user_data['vk'] = trim($this->getJson($request, 'vk'));
        $user_data['telegram'] = trim($this->getJson($request, 'telegram'));
        $user_data['instagram'] = trim($this->getJson($request, 'instagram'));
        $user_data['ok'] = trim($this->getJson($request, 'ok'));

        $user_data['country'] = trim($this->getJson($request, 'country_id'));
        $user_data['city'] = trim($this->getJson($request, 'city'));

        $user_data['interests'] = trim($this->getJson($request, 'interests')); //это уже есть
        $user_data['description'] = trim($this->getJson($request, 'description')); //это уже есть
        $user_data['super_power'] = trim($this->getJson($request, 'super_power'));
        $user_data['principles'] = trim($this->getJson($request, 'principles'));

        if ($is_admin) {
            $form = $this->createFormByArray(AdminUpdateUserType::class, $user_data, ['user' => $user]);
        } else {
            $form = $this->createFormByArray(UpdateUserType::class, $user_data);
        }

        if ($form->isValid()) {
            if ($email != $old_email) {
                $confirmation = $emailConfirmationServices->createCode($user);
                $queueServices->sendEmail(
                    $user_data['email'],
                    'Подтверждение почты на сервисе',
                    '/mail/user/registration/code.html.twig',
                    [
                        'code' => $confirmation->getCode(),
                        'user_id' => $user->getId()
                    ]
                );

                $email_is_changed = true;
            }

            try {
                $userServices->setUpdateData($user, $form, $roles, $email_is_changed, $is_admin);
            } catch (LogicException $e) {
                return $this->jsonError(['#' => $e->getMessage()]);
            }

            return $this->jsonSuccess([
                'email_changed' => $email_is_changed
            ]);
        } else {
            return $this->formValidationError($form);
        }
    }

   
      
       @Route("/api/user", name="api_userinfo", methods={"GET"})
      
       @OA\Parameter(in="query", name="id", schema={"type"="integer", "example"=1}, description="ID пользователя"),
      
       @OA\Response(response=200, description="Информация предоставлена")
       @OA\Response(response=401, description="Необходима авторизация")
      
       @OA\Tag(name="user")
       @Security(name="Bearer")
   
    public function userInfoAction(
        CoreSecurity $security,
        UserServices $userServices,
        Request $request,
        UserRepository $userRepository
    ): Response {
        $id = (int)$request->query->get('id');
        $user = $security->getUser();
        if ($user->getIsAdmin() && !empty($id)) {
            $user = $userRepository->find($id);
            if ($user) {
                $result = $userServices->getInformation($user);
                return $this->jsonSuccess(['user' => $result]);
            }
        }

        $result = $userServices->getInformation($user);
        return $this->jsonSuccess(['user' => $result]);
    }

   
      
       @Route("/api/user/init", name="api_set_init_data", methods={"POST"})
      
       @OA\RequestBody(
         @OA\MediaType(
           mediaType="application/json",
           @OA\Schema(
             type="object",
             @OA\Property(property="promo", type="string", description="Промокод", example="")
           )
         )
       )
      
       @OA\Response(response=200, description="Информация обновлена")
       @OA\Response(response=401, description="Необходима авторизация")
      
       @OA\Tag(name="user")
       @Security(name="Bearer")
   
    public function setInitDataAction(
        Request $request,
        CoreSecurity $security,
        PromocodeServices $promocodeServices,
        PromocodeActionFactory $promocodeActionFactory
    ): Response {
        $user = $security->getUser();
        $promo_entity = null;
        $promo = trim($this->getJson($request, 'promo'));

        if ($promo) {
            $promo_entity = $promocodeServices->getPromoByCode($promo);
            try {
                $promocodeServices->validate($user, $promo_entity);
            } catch (LogicException $e) {
                return $this->jsonError(['promo' => $e->getMessage()]);
            }
        }

        if ($promo_entity) {
            $factory = $promocodeActionFactory
                ->setPromocodeService($promocodeServices)
                ->getPromoAction($promo_entity);

            if ($factory) {
                $factory->registration($user, $promo_entity);
                $promocodeServices->savePromocode($user, $promo_entity);
                return $this->jsonSuccess(["Промокод успешно сохранен"]);
            } else {
                return $this->jsonError(['promo' => "Промокод не найден"]);
            }
        }
    }

   
      
       @Route("/api/user/promo", name="api_user_promo", methods={"POST"})
      
       @OA\RequestBody(
         @OA\MediaType(
           mediaType="application/json",
           @OA\Schema(
             type="object",
             @OA\Property(property="phone", type="integer", description="Номер телефона", example="+79000001000")
           )
         )
       )
      
       @OA\Response(response=200, description="Промокод отправлен")
       @OA\Response(response=400, description="Ошибка при отправке SMS")
       @OA\Response(response=403, description="Не удалось отправить приглашение")
       @OA\Response(response=401, description="Необходима авторизация")
      
       @OA\Tag(name="user")
       @Security(name="Bearer")
   
    public function sendpromoAction(
        Request $request,
        CoreSecurity $security,
        QueueServices $queueServices,
        SMSServices $SMSServices,
        UserServices $userServices,
        PromocodeServices $promocodeServices,
        KernelInterface $kernel
    ): Response {
        $user = $security->getUser();
        $phone = (string)$this->getJson($request, 'phone');
        $formatted_phone = $SMSServices->phoneFormat($phone);

        if (!$formatted_phone) {
            return $this->jsonError(['phone' => 'Введите корректный номер телефона'], 400);
        }

        if ($promocodeServices->userInvited($formatted_phone)) {
            return $this->jsonError(['phone' => 'Пользователь уже приглашен'], 403);
        }

        if ($formatted_phone == $user->getPhone()) {
            return $this->jsonError(['phone' => 'Вы уже зарегистрированы в системе :)'], 403);
        }

        if ($userServices->phoneExists($formatted_phone)) {
            return $this->jsonError(['phone' => 'Данный номер уже зарегистрирован в программе'], 403);
        }

        $code = $promocodeServices->generateCode();
        $link = $this->params->get('base.url');

        $message = 'Вас приглашает ' . $user->getFirstName() . ' ' . $user->getLastName() . ' ';
        $message .= 'По промокоду ' . $code . ' получите бонус! ' . $link;

        $status = $kernel->getEnvironment() == 'dev' ? true : $queueServices->sendSMS($formatted_phone, trim($message));

        if ($status) {
            $promocodeServices->createInviteCode($user, $formatted_phone, $code);

            return $this->jsonSuccess(['sms_status' => $status]);
        } else {
            return $this->jsonError(['sms_status' => $status]);
        }
    }

   
      
       @Route("/api/user/profile/{id}", requirements={"id"="\d+"}, name="api_user_profiles", methods={"GET"})
      
       @OA\Parameter(name="id", in="path", description="ID пользователя",
           @OA\Schema(type="integer", example="1")
       )
      
       @OA\Response(response=200, description="Информация предоставлена")
       @OA\Response(response=401, description="Необходима авторизация")
       @OA\Response(response=404, description="Пользователь не найден")
      
       @OA\Tag(name="user")
       @Security(name="Bearer")
   
    public function userProfileInfoAction(
        UserServices $userServices,
        int $id
    ): Response {
        $user_info = $userServices->getUserProfileInfo($id);
        if (empty($user_info)) {
            return $this->jsonError(['result' => null], 404);
        }

        return $this->jsonSuccess(['result' => $user_info]);
    }
}
