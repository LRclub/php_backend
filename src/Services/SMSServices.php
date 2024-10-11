<?php

namespace App\Services;

use App\Entity\AuthCode;
use App\Entity\Promocodes;
use App\Interfaces\SMSProvider;
use App\Repository\AuthCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SMSServices
{
    //лимит по которому проверяем количество запросов за крайнее количество секунд
    public const MAX_REQUESTS_TIME_LIMIT = 300;
    //лимит на количество запросов в рамках лимита времени
    public const MAX_REQUESTS_COUNT_LIMIT = 5;
    //время жизни кода
    public const LIFETIME_CODE = 900;
    //количество попыток ввода кода
    public const AVAILABLE_ATTEMPTS = 25; // Временное повышение лимита с 5 до 25 на время работ


    private ParameterBagInterface $params;
    private EntityManagerInterface $em;
    private RandomizeServices $randomizeServices;
    private AuthCodeRepository $authCodeRepository;
    private SMSProvider $SMSProvider;

    public function __construct(
        EntityManagerInterface $em,
        ParameterBagInterface $params,
        RandomizeServices $randomizeServices,
        AuthCodeRepository $authCodeRepository,
        SMSProvider $SMSProvider
    ) {
        $this->em = $em;
        $this->params = $params;
        $this->randomizeServices = $randomizeServices;
        $this->authCodeRepository = $authCodeRepository;
        $this->SMSProvider = $SMSProvider;
    }

    /**
     * Приводим номер телефона к единому формату с +7, без спец. символов.
     * Если номер не валиден, возвращаем null
     *
     * @param String $number
     * @return string|null
     */
    public function phoneFormat(string $number): ?string
    {
        $number = preg_replace('/\D/', '', $number);
        $first_number = substr($number, 0, 1);

        if (strlen($number) != 11 || ($first_number != 7 && $first_number != 8)) {
            return null;
        }

        if ($first_number == 8) {
            $number = '7' . substr($number, 1);
        }

        return '+' . $number;
    }


    /**
     * Возвращаем код авторизации
     *
     * @param string $formatted_phone
     * @param string $ip
     * @param string $role
     * @return AuthCode|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function createCode(string $formatted_phone, string $ip, string $role): ?AuthCode
    {
        $auth_code = $this->getSimpleCodes();

        $code = new AuthCode();
        $code->setTime(time())
            ->setAttempts(0)
            ->setIP($ip)
            ->setPhone($formatted_phone)
            ->setRole($role)
            ->setIsCompleted(false)
            ->setIsMailed(false)
            ->setCode($auth_code);

        $this->em->persist($code);
        $this->em->flush();

        return $code;
    }

    public function getCodeItem(string $code_id): ?AuthCode
    {
        return $this->authCodeRepository->getCode($code_id);
    }

    /**
     * Увеличиваем количество попыток
     *
     * @param AuthCode $authCode
     */
    public function increaseAttempts(AuthCode $authCode)
    {
        $authCode->setAttempts($authCode->getAttempts() + 1);

        $this->em->persist($authCode);
        $this->em->flush();
    }

    /**
     * Увеличиваем количество попыток
     *
     * @param AuthCode $authCode
     */
    public function setMailed(AuthCode $authCode)
    {
        $authCode->setIsMailed(true);

        $this->em->persist($authCode);
        $this->em->flush();
    }


    /**
     * Отметить завершенным
     *
     * @param AuthCode $authCode
     */
    public function markCompleted(AuthCode $authCode)
    {
        $authCode->setIsCompleted(true);

        $this->em->persist($authCode);
        $this->em->flush();
    }

    /**
     * Возвращаем, можем ли мы отправить запрос сейчас или уперлись в лимит количества отправок
     *
     * @param string $ip
     * @param string $phone
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkAvailability(string $ip, string $phone): bool
    {
        $counts = $this->authCodeRepository->countCodeRequests($ip, $phone, time() - self::MAX_REQUESTS_TIME_LIMIT);

        return $counts < self::MAX_REQUESTS_COUNT_LIMIT;
    }

    /**
     * Отправка SMS
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function sendSMS(string $phone, string $message): bool
    {
        return $this->SMSProvider->sendSMS($phone, $message);
    }

    /**
     * Получение баланса
     *
     * @return [type]
     */
    public function getBalance()
    {
        return $this->SMSProvider->getBalance();
    }

    /**
     * Отправка путем звонка
     *
     * @param string $phone
     * @param string $code
     * @return bool
     */
    public function sendCall(string $phone, string $code): bool
    {
        return $this->SMSProvider->sendCall($phone, $code);
    }

    /**
     * Возвращаем коды легкие для запоминания из повторяющихся чисел
     *
     * @return string
     */
    private function getSimpleCodes(): string
    {
        $rand_nums = [];
        for ($i = 0; $i < 3; $i++) {
            $rand_nums[] = rand(0, 9);
        }
        $nums_string = implode('', array_unique($rand_nums));

        return $this->randomizeServices->generateString(4, $nums_string);
    }

    /**
     * Валидация кода
     *
     * @param AuthCode $auto_code
     * @return array|null
     */
    public function codeValidation(?AuthCode $auto_code): ?array
    {
        if (!$auto_code) {
            return ['status' => 404, 'error' => ['code_id' => 'Код не найден!']];
        }

        if ($auto_code->getIsCompleted()) {
            return ['status' => 403, 'error' => ['code_id' => 'Код уже использован!']];
        }

        if ($auto_code->getAttempts() > SMSServices::AVAILABLE_ATTEMPTS) {
            return ['status' => 429, 'error' => ['code_id' => 'Превышено количество попыток! Запросите новый код']];
        }

        if ($auto_code->getTime() + SMSServices::LIFETIME_CODE < time()) {
            return ['status' => 403, 'error' => ['code_id' => 'Код устарел, запросите новый!']];
        }

        return null;
    }
}
