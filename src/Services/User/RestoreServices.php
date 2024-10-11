<?php

namespace App\Services\User;

use App\Entity\PhoneRestoreHistory;
use App\Entity\AuthCode;
use App\Entity\ChangeUserPassword;
use App\Entity\ChangeUserPhone;
use App\Entity\User;
use App\Repository\ChangeUserPhoneRepository;
use App\Repository\ChangeUserPasswordRepository;
use App\Services\RandomizeServices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RestoreServices
{
    //задержка между повторными запросами смены номера
    public const EMAIL_LIFE_TIME = 300;

    private EntityManagerInterface $em;
    private ChangeUserPhoneRepository $changeUserPhoneRepository;
    private ChangeUserPasswordRepository $changeUserPasswordRepository;
    private UserPasswordHasherInterface $userPasswordHasher;
    private RandomizeServices $randomizeServices;

    public function __construct(
        EntityManagerInterface $em,
        ChangeUserPhoneRepository $changeUserPhoneRepository,
        ChangeUserPasswordRepository $changeUserPasswordRepository,
        UserPasswordHasherInterface $userPasswordHasher,
        RandomizeServices $randomizeServices
    ) {
        $this->em = $em;
        $this->changeUserPhoneRepository = $changeUserPhoneRepository;
        $this->changeUserPasswordRepository = $changeUserPasswordRepository;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->randomizeServices = $randomizeServices;
    }

    /**
     * Создание запроса на смену пароля
     *
     * @param User $user
     * @return ChangeUserPhone
     */
    public function createRequest(User $user)
    {
        $reset = new ChangeUserPhone();
        $reset
            ->setCode(md5(RandomizeServices::generateString(32)))
            ->setUser($user)
            ->setConfirmTime(0)
            ->setCreateTime(time())
            ->setIsConfirmed(false);

        $this->em->persist($reset);
        $this->em->flush();

        return $reset;
    }

    public function createPasswordRequest(User $user)
    {
        $reset = new ChangeUserPassword();
        $reset
            ->setCode(md5(RandomizeServices::generateString(32)))
            ->setUser($user)
            ->setCreateTime(time())
            ->setIsConfirmed(false);

        $this->em->persist($reset);
        $this->em->flush();

        return $reset;
    }

    /**
     * Проверка кода активации
     *
     * @param int $user_id
     * @param $code
     * @return ChangeUserPhone|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkCode(int $user_id, $code): ?ChangeUserPhone
    {
        return $this->changeUserPhoneRepository->findByUserIdAndCode($user_id, $code);
    }

    /**
     * Проверка кода смены пароля
     *
     * @param int $user_id
     * @param $code
     * @return ChangeUserPhone|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkPasswordCode(int $user_id, $code): ?ChangeUserPassword
    {
        return $this->changeUserPasswordRepository->findByUserIdAndCode($user_id, $code);
    }

    /**
     * Возвращаем информацию о крайнем запросе
     *
     * @param User $user
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLastTimeSend(User $user): ?ChangeUserPhone
    {
        return $this->changeUserPhoneRepository->findByUserId($user->getId());
    }

    public function getLastTimeSendPassword(User $user): ?ChangeUserPassword
    {
        return $this->changeUserPasswordRepository->findLastRestore($user);
    }

    /**
     * Обновление номера телефона
     *
     * @param ChangeUserPhone $restore
     * @param AuthCode $authCode
     * @return int|mixed|string|null
     */
    public function updatePhone(ChangeUserPhone $restore, AuthCode $authCode)
    {
        $restore->setIsConfirmed(true)
            ->setConfirmTime(time());

        $authCode->setIsCompleted(true);

        $user = $restore->getUser();
        $user->setPhone($authCode->getPhone());

        $this->em->persist($restore);
        $this->em->persist($authCode);
        $this->em->persist($user);
        $this->em->flush();
    }


    /**
     * Сохранение истории изменения номера
     *
     * @param ChangeUserPhone $restore
     * @param AuthCode $authCode
     *
     * @return PhoneRestoreHistory
     */
    public function saveRestorePhoneHistory(ChangeUserPhone $restore, AuthCode $authCode): PhoneRestoreHistory
    {
        $user = $restore->getUser();
        $history = new PhoneRestoreHistory();
        $history
            ->setUser($user)
            ->setLastPhone($user->getPhone())
            ->setNewPhone($authCode->getPhone())
            ->setUpdateTime(date('Y-m-d H:i:s'));

        $this->em->persist($history);
        $this->em->flush();

        return $history;
    }
}
