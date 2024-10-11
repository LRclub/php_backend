<?php

namespace App\Services\Providers;

use App\Interfaces\SMSProvider;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SMSAeroServices implements SMSProvider
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
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
        $link = "https://gate.smsaero.ru/v2/sms/send?number=";
        $request = file_get_contents(
            $link . $phone . "&text=" . urlencode($message) . "&sign=" . $this->params->get('smsaero.sign'),
            false,
            $this->httpContext()
        );

        if ($this->isJson($request)) {
            $response = json_decode($request, true);

            return (!empty($response['success']));
        }

        return false;
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
        $link = "https://gate.smsaero.ru/v2/flashcall/send?phone=";
        $request = file_get_contents(
            $link . $phone . "&code=" . $code,
            false,
            $this->httpContext()
        );

        if ($this->isJson($request)) {
            $response = json_decode($request, true);

            return (!empty($response['success']));
        }

        return false;
    }

    /**
     * Получение баланса
     *
     * @return float
     */
    public function getBalance(): float
    {
        $link = "https://gate.smsaero.ru/v2/balance";
        $response = file_get_contents(
            $link,
            false,
            $this->httpContext()
        );
        $response = json_decode($response, true);

        if (!isset($response['data']['balance'])) {
            throw new Exception('Отсутствует баланс в теле ответа!');
        }

        return (float)$response['data']['balance'];
    }


    /**
     * Контекст для file_get_contents
     *
     * @return resource
     */
    private function httpContext()
    {
        $auth = base64_encode($this->params->get('smsaero.login') . ':' . $this->params->get('smsaero.api_key'));
        $context = stream_context_create([
            "http" => [
                "header" => "Authorization: Basic $auth",
                /*    'ignore_errors' => true */
            ]
        ]);

        return $context;
    }

    private function isJson($string)
    {
        return ((is_string($string) &&
            (is_object(json_decode($string)) ||
                is_array(json_decode($string))))) ? true : false;
    }
}
