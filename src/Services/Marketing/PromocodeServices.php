<?php

namespace App\Services\Marketing;

use App\Entity\Promocodes;
use App\Entity\PromocodesUsed;
use App\Entity\User;
use App\Repository\PromocodesRepository;
use App\Repository\PromocodesUsedRepository;
use App\Services\Marketing\PromocodeAction\PromocodeActionFactory;
use App\Services\RandomizeServices;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\LogicException;

class PromocodeServices
{
    public const INVITE_CODE = 'invite';
    public const DISCOUNT_CODE = 'discount';

    private EntityManagerInterface $em;
    private PromocodesRepository $promocodesRepository;
    private PromocodesUsedRepository $promocodesUsedRepository;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $em,
        PromocodesRepository $promocodesRepository,
        PromocodesUsedRepository $promocodesUsedRepository,
        UserRepository $userRepository
    ) {
        $this->em = $em;
        $this->promocodesRepository = $promocodesRepository;
        $this->promocodesUsedRepository = $promocodesUsedRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Создание запроса на инвайт пользователя
     *
     * @param User $user
     * @param string $phone
     * @param string $code
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function createInviteCode(User $user, string $phone, string $code)
    {
        $item = new Promocodes();
        $item->setOwner($user)
            ->setAction(self::INVITE_CODE)
            ->setPhone($phone)
            ->setIsActive(true)
            ->setDescription('Приглашение пользователя по номеру: ' . $phone)
            ->setCode($code);

        $this->em->persist($item);
        $this->em->flush();
    }

    /**
     * Пригласили пользователя уже или нет
     *
     * @param string $phone
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function userInvited(string $phone)
    {
        return !empty($this->getPromoByPhone($phone));
    }


    /**
     * Возвращаем промокод по номеру телефона
     *
     * @param string $phone
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPromoByPhone(string $phone)
    {
        return $this->promocodesRepository->findByPhone($phone);
    }

    /**
     * Возвращаем промокоды пользователя
     *
     * @param User $user
     * @return array
     */
    public function getUserCodes(User $user, array $invited_users)
    {
        $result = [];
        $registered = $this->getRegisteredByInvite($invited_users);

        foreach ($user->getPromocodes() as $val) {
            $result[] = [
                'phone' => $val->getPhone(),
                'code' => $val->getCode(),
                'action' => $val->getAction(),
                'result' => $registered[$val->getPhone()] ?? false
            ];
        }

        return $result;
    }

    /**
     * Статистика по приглашениям
     *
     * @param array $promo_users
     * @return int[]
     */
    public function statInvites(array $promo_users): array
    {
        $result = [
            'invited' => 0,
            'registered' => 0,
            'filled' => 0,
        ];

        foreach ($promo_users as $item) {
            $result['invited']++;

            if (!empty($item['result'])) {
                $result['registered']++;
            }

            if (!empty($item['result']['status'])) {
                $result['filled']++;
            }
        }

        return $result;
    }

    /**
     * Возвращаем свободный случайны промокод, которого нет в базе
     *
     * @return string
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function generateCode(): string
    {
        //лимитируем количество попыток для повторной генерации
        for ($i = 0; $i < 50; $i++) {
            $random = RandomizeServices::generateString(4, '0123456789abcdefghijklmnopqrstuvwxyz');
            $exists = $this->promocodesRepository->findByCode($random);

            if (!$exists) {
                return $random;
            }
        }

        throw new \Exception('Проблема с генерацией промокодов. Свободные промокоды отсутствуют!');
    }

    /**
     * Поиск владельца по промокоду
     *
     * @param mixed $promocode
     *
     * @return [type]
     */
    public function getPromoByCode($promocode)
    {
        return $this->promocodesRepository->findOneBy(['code' => $promocode]);
    }


    public function validate($user, $promocode)
    {
        return $this->validateWithoutUser($promocode) && $this->validateUser($user, $promocode);
    }

    public function validateWithoutUser(?Promocodes $promocode)
    {
        if (empty($promocode)) {
            throw new LogicException("Промокод не существует");
        }

        if (!$promocode->getIsActive() || $promocode->getIsDeleted()) {
            throw new LogicException("Промокод не существует");
        }

        if ($promocode->getEndTime() && $promocode->getEndTime() < time()) {
            throw new LogicException("Промокод не существует");
        }

        if ($promocode->getStartTime() && $promocode->getStartTime() > time()) {
            throw new LogicException("Промокод не существует");
        }

        if ($promocode->getAmount() && $promocode->getAmount() <= $promocode->getAmountUsed()) {
            throw new LogicException("Промокод не существует");
        }

        if ($promocode->getOwner() && $promocode->getOwner()->getIsBlocked()) {
            throw new LogicException("Промокод не существует");
        }

        return true;
    }

    public function validateUser($user, $promocode)
    {
        /* if (!$this->isPromocodeAvailable($user)) {
            throw new LogicException("Ввод промокода больше недоступен");
        } */

        if ($this->promocodesUsedRepository->findOneBy(['user' => $user->getId()])) {
            throw new LogicException("Вы уже ввели промокод");
        }

        if ($promocode->getPhone() && $promocode->getPhone() != $user->getPhone()) {
            throw new LogicException("Промокод не существует");
        }

        if ($promocode->getOwner() == $user) {
            throw new LogicException("Нельзя указать свой промокод");
        }

        return true;
    }

    public function isPromocodeAvailable($user): bool
    {
        if (
            $user->getCreateTime() < time() - (24 * 60 * 60)
            || $user->getInvited()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Сохранение промокода для пользователя
     *
     * @param mixed $user
     * @param mixed $promocode
     *
     * @return [type]
     */
    public function savePromocode($user, $promocode)
    {
        $promocode_used = new PromocodesUsed();
        $promocode_used->setPromocode($promocode)->setUser($user)->setActivationTime(time());
        $this->em->persist($promocode_used);

        $promocode->setAmountUsed($promocode->getAmountUsed() + 1);
        if ($promocode->getPhone()) {
            $promocode->setIsDeleted(true);
        }

        $this->em->persist($promocode);
        $this->em->flush();
        return true;
    }

    /**
     * Создание промокода админом
     *
     * @param mixed $form
     *
     * @return [type]
     */
    public function createPromocode($form)
    {
        $code = $form->get('code')->getData();
        $description = $form->get('description')->getData();
        $start_time = $form->get('start_time')->getData() ?? 0;
        $discount_percent = $form->get('discount_percent')->getData();

        if ($start_time) {
            $start_time = $start_time->getTimestamp();
        }
        $end_time = $form->get('end_time')->getData() ?? 0;
        if ($end_time) {
            $end_time = $end_time->getTimestamp();
        }
        $amount = $form->get('amount')->getData();
        $is_active = $form->get('is_active')->getData();
        $action = $form->get('action')->getData();

        $promocode = new Promocodes();
        $promocode
            ->setCode($code)
            ->setDescription($description)
            ->setStartTime($start_time)
            ->setEndTime($end_time)
            ->setAmount($amount)
            ->setDiscountPercent($discount_percent)
            ->setIsActive($is_active)
            ->setAction($action);

        $this->em->persist($promocode);
        $this->em->flush();
    }

    /**
     * Редактирование промокода админом
     *
     * @param mixed $form
     *
     * @return [type]
     */
    public function editPromocode($form)
    {
        $id = $form->get('promocode_id')->getData();
        $promocode = $this->promocodesRepository->find($id);

        $code = $form->get('code')->getData();
        $description = $form->get('description')->getData();
        $start_time = $form->get('start_time')->getData() ?? 0;
        $discount_percent = $form->get('discount_percent')->getData() ?? null;

        if ($start_time) {
            $start_time = $start_time->getTimestamp();
        }
        $end_time = $form->get('end_time')->getData() ?? 0;
        if ($end_time) {
            $end_time = $end_time->getTimestamp();
        }
        $amount = $form->get('amount')->getData();
        $is_active = $form->get('is_active')->getData();
        $action = $form->get('action')->getData();

        $promocode
            ->setCode($code)
            ->setDescription($description)
            ->setStartTime($start_time)
            ->setEndTime($end_time)
            ->setAmount($amount)
            ->setDiscountPercent($discount_percent)
            ->setIsActive($is_active)
            ->setAction($action);

        $this->em->persist($promocode);
        $this->em->flush();
    }

    /**
     * @param mixed $promocode_id
     *
     * @return [type]
     */
    public function deletePromocode(int $promocode_id)
    {
        $promocode = $this->promocodesRepository->find($promocode_id);
        if (!$promocode) {
            throw new LogicException("Промокод не найден");
        }

        if ($promocode->getIsDeleted()) {
            throw new LogicException("Промокод уже удален");
        }

        $promocode->setIsDeleted(true);
        $this->em->persist($promocode);
        $this->em->flush();
        return true;
    }

    /**
     * @param mixed $promocode_id
     *
     * @return [type]
     */
    public function setIsActivePromocode(int $promocode_id, bool $is_active)
    {
        $promocode = $this->promocodesRepository->find($promocode_id);
        if (!$promocode) {
            throw new LogicException("Промокод не найден");
        }
        $promocode->setIsActive($is_active);
        $this->em->persist($promocode);
        $this->em->flush();
        return true;
    }

    /**
     * Если пользователя приглашали, вводим промокод
     *
     * TODO подумать об использовании
     *
     * @param mixed $user
     *
     * @return [type]
     */
    public function getAvailablePromo($user)
    {
        $existed_promocode = $this->promocodesRepository->findByPhone($user->getPhone());
        if (!$existed_promocode) {
            throw new LogicException("Промокод не существует");
        }
        $user->setInvited($existed_promocode->getOwner());
        $this->em->persist($user);
        $this->em->flush();
        return true;
    }

    /**
     * Список промокодов
     *
     * @param array $order_by
     * @param string $search
     *
     * @return [type]
     */
    public function getPromocodes(
        array $order_by,
        string $search = ""
    ) {
        $promocodes = $this->promocodesRepository->getPromocodesAdmin(
            $order_by,
            $search
        );
        $result = [];
        if (!$promocodes) {
            return $result;
        }

        foreach ($promocodes as $promocode) {
            $promo_info = reset($promocode);
            $result[] = [
                'id' => $promo_info->getId(),
                'code' => $promo_info->getCode(),
                'description' => $promo_info->getDescription(),
                'start_time' => $promo_info->getStartTime(),
                'end_time' => $promo_info->getEndTime(),
                'amount' => $promo_info->getAmount() ?? 0,
                'amount_user' => $promo_info->getAmountUsed() ?? 0,
                'used_count' => $promocode['used_count'],
                'is_active' => $promo_info->getIsActive(),
            ];
        }

        return $result;
    }

    /**
     * Получение промокода по id
     *
     * @return [type]
     */
    public function getPromocodeById($id)
    {
        $promocode = $this->promocodesRepository->find($id);
        $result = [];
        if (!$promocode) {
            return $result;
        }

        $result[] = [
            'id' => $promocode->getId(),
            'code' => $promocode->getCode(),
            'description' => $promocode->getDescription(),
            'start_time' => $promocode->getStartTime(),
            'end_time' => $promocode->getEndTime(),
            'amount' => $promocode->getAmount() ?? 0,
            'amount_user' => $promocode->getAmountUsed() ?? 0,
            'is_active' => $promocode->getIsActive(),
            'is_deleted' => $promocode->getIsDeleted(),
        ];

        return $result;
    }

    /**
     * Возвращаем зарегистрированных пользователей по приглашению
     *
     * @param array $invited_users
     * @return array
     */
    private function getRegisteredByInvite(array $invited_users): array
    {
        $registered = array_map(function ($v) {
            return array_merge([
                'phone' => $v->getPhone()
            ], $v->userInviteStatus());
        }, $invited_users);

        return array_combine(array_column($registered, 'phone'), $registered);
    }

    /**
     * Проверка промокода при оплате
     *
     * @return [type]
     */
    public function validatePaymentPromocode($promocode, $user)
    {
        $promocode = $this->getPromoByCode($promocode);
        if (!$promocode) {
            throw new LogicException("Промокод не найден");
        }

        if ($promocode->getIsDeleted() || !$promocode->getIsActive()) {
            throw new LogicException("Промокод не найден");
        }

        if ($promocode->getAction() != self::DISCOUNT_CODE) {
            throw new LogicException("Данный промокод использовать нельзя");
        }

        if ($promocode->getEndTime() && $promocode->getEndTime() < time()) {
            throw new LogicException("Данный промокод использовать нельзя");
        }

        if ($promocode->getStartTime() && $promocode->getStartTime() > time()) {
            throw new LogicException("Данный промокод использовать нельзя");
        }

        if ($promocode->getAmount() && $promocode->getAmount() <= $promocode->getAmountUsed()) {
            throw new LogicException("Данный промокод использовать нельзя");
        }

        $promocodes_used = $this->promocodesUsedRepository->findOneBy([
            'promocode' => $promocode->getId(),
            'user' => $user->getId()
        ]);

        if ($promocodes_used) {
            throw new LogicException("Промокод уже использован");
        }

        return $promocode;
    }
}
