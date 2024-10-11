<?php

namespace App\Interfaces;

interface SMSProvider
{
    /**
     * Отправка смс сообщений
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function sendSMS(string $phone, string $message): bool;

    /**
     * Отправка кода путем звонка
     * @param string $phone
     * @param string $code
     * @return bool
     */
    public function sendCall(string $phone, string $code): bool;

    /**
     * Получение баланса
     *
     * @return array
     */
    public function getBalance(): float;
}
