<?php

namespace App\Services\User;

// Components
use App\Repository\CountriesRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\Exception\LogicException;
// Entity
use App\Entity\AuthCode;
use App\Entity\Notifications;
use App\Entity\Promocodes;
use App\Entity\User;
use App\Entity\UserEventHistory;
// Repository
use App\Repository\UserRepository;
use App\Repository\FeedbackMessageRepository;
use App\Repository\ChangeUserPasswordRepository;
use App\Repository\NoticeRepository;
use App\Repository\UserEventHistoryRepository;
use App\Repository\RecostingRepository;
use App\Repository\NotificationsRepository;
// Services
use App\Services\Marketing\PromocodeServices;
use App\Services\RandomizeServices;
use App\Services\HelperServices;
use App\Services\TwigServices;
use App\Services\User\TokenServices;
use App\Services\File\UserFileServices;
use App\Services\Notice\NoticeServices;
use App\Services\Event\UserEventHistoryServices;
// Etc
use Doctrine\ORM\EntityManagerInterface;

class UserServices
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $userPasswordHasher;
    private FormFactoryInterface $formFactory;
    private UserRepository $userRepository;
    private RandomizeServices $randomizeServices;
    private PromocodeServices $promocodeServices;
    private CoreSecurity $security;
    private RecostingRepository $recostingRepository;
    private NotificationsRepository $notificationsRepository;
    private UserEventHistoryRepository $userEventHistoryRepository;
    private TokenServices $tokenServices;
    private NoticeRepository $noticeRepository;
    private UserFileServices $userFileServices;
    private NoticeServices $noticeServices;
    private UserEventHistoryServices $userEventHistoryServices;
    private CountriesRepository $countriesRepository;


    public function __construct(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $userPasswordHasher,
        FormFactoryInterface $formFactory,
        UserRepository $userRepository,
        RandomizeServices $randomizeServices,
        PromocodeServices $promocodeServices,
        CoreSecurity $security,
        TokenServices $tokenServices,
        UserFileServices $userFileServices,
        NoticeRepository $noticeRepository,
        UserEventHistoryRepository $userEventHistoryRepository,
        RecostingRepository $recostingRepository,
        NotificationsRepository $notificationsRepository,
        NoticeServices $noticeServices,
        UserEventHistoryServices $userEventHistoryServices,
        CountriesRepository $countriesRepository
    ) {
        $this->em = $em;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->formFactory = $formFactory;
        $this->userRepository = $userRepository;
        $this->randomizeServices = $randomizeServices;
        $this->promocodeServices = $promocodeServices;
        $this->security = $security;
        $this->tokenServices = $tokenServices;
        $this->userFileServices = $userFileServices;
        $this->noticeRepository = $noticeRepository;
        $this->userEventHistoryRepository = $userEventHistoryRepository;
        $this->recostingRepository = $recostingRepository;
        $this->notificationsRepository = $notificationsRepository;
        $this->noticeServices = $noticeServices;
        $this->userEventHistoryServices = $userEventHistoryServices;
        $this->countriesRepository = $countriesRepository;
    }

    /**
     * Создание аккаунта пользователя в базе
     *
     * @param User $user
     * @param AuthCode $authCode
     * @return User
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function createUser(AuthCode $authCode): User
    {
        $phone = $authCode->getPhone();
        $promo = $authCode->getPromo();

        $user = new User();
        $user->setPhone($phone)
            ->setPassword($this->userPasswordHasher->hashPassword($user, $this->randomizeServices->generateString()))
            ->setIsBlocked(false)
            ->setIsConfirmed(false)
            ->setCreateTime(time())
            ->setLastVisitTime(0);

        //если есть промокод, выставляем, кто пригласил
        if (!empty($promo) && empty($user->getInvited())) {
            $user->setInvited($promo->getOwner());
        }

        $this->em->persist($user);

        $notifications = new Notifications();
        $notifications
            ->setNewMaterials(false)
            ->setSubscriptionHistory(false)
            ->setEmailNotice(false)
            ->setUser($user);
        $this->em->persist($notifications);
        $this->em->flush();

        return $user;
    }

    public function userAuth($email)
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user->getIsBlocked()) {
            throw new LogicException("Пользователь заблокирован");
        }

        $result = $this->tokenServices->createPair($user);
        return ['user' => $user, 'token' => $result];
    }

    /**
     * Выставляем время крайнего визита
     *
     * @param User $user
     */
    public function updateLastVisit(User $user)
    {
        //если с крайнего обновления меньше 5 минут, пропускаем
        if (time() - $user->getLastVisitTime() > 300) {
            $user->setLastVisitTime(time());

            $this->em->persist($user);
            $this->em->flush();
        }
    }

    /**
     * Проверка на наличие номера телефона
     *
     * @param string $phone
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function phoneExists(string $phone)
    {
        return !empty($this->userRepository->findByPhone($phone));
    }

    /**
     * Возвращаем приглашенных пользователей
     *
     * @param User $user
     * @return int|mixed[]|string
     */
    public function getInvitedUsers(User $user)
    {
        return $this->userRepository->findInvitedByUserId($user->getId());
    }


    /**
     * Метод для ПУБЛИЧНОГО предоставления данных!
     * Отдает данные по другим пользователям по запросу.
     * Конфиденциальную информацию здесь не отдавать!
     *
     * @param int $id
     * @return array|null
     */
    public function getUserProfileInfo(int $id)
    {
        $user = $this->userRepository->findOneBy([
            'id' => $id,
            'is_blocked' => 0
        ]);

        if (!$user) {
            return null;
        }

        $is_vip = false;

        $last_recosting = $this->recostingRepository->findOneBy(['user' => $user], ['id' => 'DESC']);

        if ($last_recosting) {
            $is_vip = $user->getSubscriptionEndDate() > time() && $last_recosting->getIsVip();
        }


        $user_data = $user->getUserProfileArrayData();
        $user_data['avatar'] = $this->userFileServices->getAvatar($user);
        $user_data['is_vip'] = $is_vip;

        return $user_data;
    }


    /**
     * Возвращаем информацию по пользователю
     *
     * @param UserInterface $user
     * @return array[]
     */
    public function getInformation(UserInterface $user): array
    {
        $invited_users = $this->getInvitedUsers($user);
        $promo_users = $this->promocodeServices->getUserCodes($user, $invited_users);

        // Set birthday
        $birthday = $user->getBirthday();
        if ($birthday) {
            $birthday = $birthday->format('Y-m-d');
        } else {
            $birthday = null;
        }

        $result = [
            'id' => $user->getId(),
            'roles' => $user->getRoles(),
            'is_admin' => $user->getIsAdmin(),
            'is_moderator' => $user->getIsModerator(),
            'is_editor' => $user->getIsEditor(),
            'is_logged_admin' => $user->getIsLoggedAdmin(),
            'userinfo' => [
                'phone' => $user->getPhone(),
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'patronymic_name' => $user->getPatronymicName(),
                'gender' => $user->getGender(),
                'slug'  => $user->getSlug(),
                'birthday' => $birthday,
                'interests' => $user->getInterests(),
                'description' => $user->getDescription(),
                'super_power' => $user->getSuperPower(),
                'principles' => $user->getPrinciples(),
                'location' => [
                    'country' => $user->getCountry() ? $user->getCountry()->getArray() : null,
                    'city' => $user->getCity()
                ],
                'networks' => [
                    'ok' => $user->getOk(),
                    'tg' => $user->getTelegram(),
                    'vk' => $user->getVk(),
                    'inst' => $user->getInstagram()
                ],
                'notification_new_materials' => false,
                'notification_subscription_history' => false,
                'email_notice' => false,
                'invited' => null,
                'users' => [],
                'promocodes' => $promo_users,
                'promo_statistics' => $this->promocodeServices->statInvites($promo_users),
                'is_confirmed' => $user->getIsConfirmed(),
                'is_blocked' => $user->getIsBlocked(),
                'is_empty_profile' => $user->getIsEmptyProfile(),
                'create_time' => $user->getCreateTime(),
                'last_visit_time' => $user->getLastVisitTime(),
                'last_visit_time_formatted' => $user->getOnlineTime($user),
                'avatar' => $this->userFileServices->getAvatar($user),
                'notice_unread' => [],
                'notice_count' => 0,
                'subscription_active' => false,
                'subscription_end_date' => $user->getSubscriptionEndDate(),
                'have_one_subscription' => !empty($user->getSubscriptionEndDate()),
                'subscription_is_vip' => false,
                'is_first_month' => $user->getIsFisrtMonth()
            ]
        ];

        if ($user->getNotifications()) {
            $result['userinfo']['notification_new_materials'] = $user->getNotifications()->getNewMaterials();
            $result['userinfo']['notification_subscription_history'] =
                $user->getNotifications()->getSubscriptionHistory();
            $result['userinfo']['email_notice'] = $user->getNotifications()->getEmailNotice();
        }

        if ($user->getSubscriptionEndDate()) {
            if ($user->getSubscriptionEndDate() > time()) {
                $result['userinfo']['subscription_active'] = true;
            }
        }

        $last_recosting = $this->recostingRepository->findOneBy(
            ['user' => $user],
            ['id' => 'DESC']
        );
        if ($last_recosting) {
            $result['userinfo']['subscription_is_vip'] = $last_recosting->getIsVip();
        }

        $result['userinfo']['notice_unread'] = $this->noticeServices->getUnreadNoticesAll($user);
        $result['userinfo']['notice_count'] = count($result['userinfo']['notice_unread']);

        if ($user->getInvited()) {
            $result['invited'] = [
                'id' => $user->getInvited()->getId(),
                'first_name' => $user->getInvited()->getFirstName(),
                'last_name' => $user->getInvited()->getLastName(),
            ];
        }

        if ($invited_users) {
            foreach ($invited_users as $item) {
                $result['users'][] = [
                    'id' => $item->getId(),
                    'phone' => $item->getPhone(),
                    'info' => $item->userInviteStatus()
                ];
            }
        }

        return $result;
    }

    /**
     * Возвращаем, имеет ли юзер ВИП подписку на текущий момент
     * Проверка по сути сводится к сверке двух параметров, active и если активна,
     * то является ли випом последняя актуальная подписка
     *
     * @param UserInterface $user
     * @return bool
     */
    public function userIsVip(UserInterface $user): bool
    {
        $userinfo = $this->getInformation($user);

        $is_vip = $userinfo['userinfo']['subscription_is_vip'] ?? false;
        $is_active = $userinfo['userinfo']['subscription_active'] ?? false;

        return $is_active && $is_vip;
    }

    /**
     * Выставляем роль админа
     *
     * @param User $user
     * @return User
     */
    public function setAdminRole(User $user): User
    {
        $admin_role = User::AVAILABLE_ROLES[User::ROLE_ADMIN];

        $user->setRoles([$admin_role]);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Выставляем базовую информацию о пользователе после регистрации
     *
     * @param UserInterface $user
     * @param FormInterface $form
     * @param Promocodes|null $promo
     * @return UserInterface
     */
    public function setInitData(UserInterface $user, FormInterface $form, ?Promocodes $promo): UserInterface
    {
        $patronymic_name = $form->get('patronymic_name')->getData();
        $first_name = $form->get('first_name')->getData();
        $last_name = $form->get('last_name')->getData();
        $email = $form->get('email')->getData();

        $user->setFirstName($first_name)
            ->setLastName($last_name)
            ->setPatronymicName($patronymic_name)
            ->setEmail($email);

        if (empty($slug)) {
            $slug = $this->getTranslitUrl($user);
            $user->setSlug($slug);
        }

        if (!empty($promo)) {
            $user->setInvited($promo->getOwner());
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @param UserInterface $user
     * @param FormInterface $form
     *
     * @return UserInterface
     */
    public function setUpdateData(
        UserInterface $user,
        FormInterface $form,
        array $roles,
        bool $email_is_changed,
        bool $is_admin = false
    ): UserInterface {
        $patronymic_name = $form->get('patronymic_name')->getData();
        $first_name = $form->get('first_name')->getData();
        $last_name = $form->get('last_name')->getData();
        $phone = $form->get('phone')->getData();
        $email = $form->get('email')->getData() ?? "";
        $birthday = $form->get('birthday')->getData();
        $avatar = $form->get('avatar')->getData();
        $description = $form->get('description')->getData();
        $interests = $form->get('interests')->getData();
        $super_power = $form->get('super_power')->getData();
        $principles = $form->get('principles')->getData();
        $country_id = $form->get('country')->getData();
        $city = $form->get('city')->getData();

        $inst = $form->get('instagram')->getData();
        $tg = $form->get('telegram')->getData();
        $vk = $form->get('vk')->getData();
        $ok = $form->get('ok')->getData();


        $slug = $user->getSlug();
        // Уведомления
        $email_new_materials = (bool)$form->get('email_new_materials')->getData();
        $email_subscription_history = (bool)$form->get('email_subscription_history')->getData();
        $email_notice = (bool)$form->get('email_notice')->getData();

        if ($email_is_changed) {
            $user->setIsConfirmed(false);
        }

        // Смена роли админом
        $roles_array = [];
        if (!empty($roles)) {
            foreach ($roles as $key => $role) {
                if (!in_array($role, User::AVAILABLE_ROLES)) {
                    throw new LogicException("Такой роли нет");
                }

                if ($role != User::AVAILABLE_ROLES[User::ROLE_USER]) {
                    $roles_array[] = $role;
                }
            }
        }

        $country = null;
        if (!empty($country_id)) {
            $country = $this->countriesRepository->find($country_id);
        }

        $user->setFirstName($first_name)
            ->setLastName($last_name)
            ->setPatronymicName($patronymic_name)
            ->setPhone($phone)
            ->setEmail($email)
            ->setBirthday($birthday)
            ->setInterests($interests)
            ->setDescription($description)
            ->setPrinciples($principles)
            ->setSuperPower($super_power)
            ->setOk($ok)
            ->setInstagram($inst)
            ->setTelegram($tg)
            ->setVk($vk)
            ->setCity($city)
            ->setCountry($country)
            ->setRoles($roles_array);

        if ($avatar) {
            $this->userFileServices->setAvatar($user, $avatar);
        }

        if (empty($slug)) {
            $slug = $this->getTranslitUrl($user);
            $user->setSlug($slug);
        }

        if ($is_admin) {
            $is_blocked = $form->get('admin_is_blocked')->getData();
            if ($is_blocked) {
                $user->setIsBlocked($is_blocked);
            }
        }

        // Сохранение уведомлений
        if ($user->getNotifications()) {
            $user->getNotifications()->setNewMaterials($email_new_materials);
            $user->getNotifications()->setSubscriptionHistory($email_subscription_history);
            $user->getNotifications()->setEmailNotice($email_notice);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Удаление куки авторизации
     *
     * @return [type]
     */
    public function logout()
    {
        setcookie('token', '', -1, '/');
        setcookie('PHPSESSID', '', -1);
    }

    /**
     * Сохранение входа пользователя за день
     *
     * @param mixed $user
     *
     * @return [type]
     */
    public function saveUserAuthTime($user)
    {
        if (strtotime(date("Y-m-d")) > intval($user->getLastVisitTime())) {
            $is_auth = $this->userEventHistoryRepository->checkUserAuth($user);
            if (!$is_auth) {
                $this->userEventHistoryServices->saveUserEvent($user, 'visit');
            }
        }
    }



    /**
     * @param UserInterface $user
     *
     * @return [type]
     */
    private function getTranslitUrl(UserInterface $user)
    {
        if (empty($user->getId()) || empty($user->getFirstName()) || empty($user->getLastName())) {
            return false;
        }

        $string = $user->getFirstName() . ' ' . $user->getLastName();
        return $user->getId() . '-' . HelperServices::transliteration($string);
    }
}
