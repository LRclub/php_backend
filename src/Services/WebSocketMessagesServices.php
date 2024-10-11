<?php

namespace App\Services;

use Gos\Component\WebSocketClient\Wamp\ClientFactory;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Services\User\TokenServices;

class WebSocketMessagesServices
{
    public const CHAT_URL = 'chat/';
    public const COMMENTS_URL = 'comments/';
    public const EVENTS_URL = 'events/';
    public const FEEDBACK_URL = 'feedback/';
    private const TOPIC = 'topic';

    public const CHAT_LISTEN = 'listen';

    private ParameterBagInterface $params;
    private TokenServices $tokenServices;

    public function __construct(
        ParameterBagInterface $params,
        TokenServices $tokenServices
    ) {
        $this->params = $params;
        $this->tokenServices = $tokenServices;
    }

    /**
     * Подключение к url и выполнение действия
     *
     * @param mixed $items
     *
     * @return bool
     */
    public function socketSendActionMessage($items): bool
    {
        try {
            $factory = new ClientFactory([
                'host' => $this->params->get('socket.ip'),
                'port' => $this->params->get('socket.port')
            ]);
            $client = $factory->createConnection();

            foreach ($items as $data) {
                $client->publish($data['url'], json_encode(
                    $data['result'],
                    JSON_UNESCAPED_UNICODE
                ), [$client->connect()], []);
            }

            $client->event(self::TOPIC, '');
            $client->disconnect();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Проверка валидации токена
     *
     * @param mixed $connection
     *
     * @return [type]
     */
    public function validateToken($connection)
    {
        $request = $connection->httpRequest;
        $is_authenticated = false;
        if (!$request->getHeader('Cookie')) {
            return $is_authenticated;
        }
        $cookies = explode('; ', $request->getHeader('Cookie')[0]);

        foreach ($cookies as $cookie) {
            $cookie = urldecode($cookie);
            if (strstr($cookie, 'token=') !== false) {
                $token = str_replace('token=', '', $cookie);
                $tokenData = $this->tokenServices->getTokenData($token, false);
                $user_identifier = $this->tokenServices->checkUserToken($tokenData['user_id'], $tokenData['token']);
                if ($user_identifier === null || $user_identifier->getUser()->getIsBlocked()) {
                    return $is_authenticated;
                }

                return true;
            }
        }

        return $is_authenticated;
    }

    /**
     * Получение ID пользователя
     *
     * @param mixed $connection
     *
     * @return [type]
     */
    public function getUserByConnection($connection)
    {
        $request = $connection->httpRequest;
        if (!$request->getHeader('Cookie')) {
            return null;
        }
        $cookies = explode('; ', $request->getHeader('Cookie')[0]);

        foreach ($cookies as $cookie) {
            $cookie = urldecode($cookie);
            if (strstr($cookie, 'token=') !== false) {
                $token = str_replace('token=', '', $cookie);
                $tokenData = $this->tokenServices->getTokenData($token, false);
                $user_identifier = $this->tokenServices->checkUserToken($tokenData['user_id'], $tokenData['token']);
                if ($user_identifier === null || $user_identifier->getUser()->getIsBlocked()) {
                    return null;
                }

                return $user_identifier->getUser();
            }
        }

        return null;
    }

    /**
     * @param mixed $data
     *
     * @return [type]
     */
    public function encodePublish($data)
    {
        return md5(md5(md5(json_encode($data['data'])) . $data['date'] . $this->params->get('socket.salt')));
    }

    /**
     * Валидация ивента перед отправкой
     *
     * @param mixed $connection
     * @param mixed $event
     *
     * @return [type]
     */
    public function validatePublish($event)
    {
        $event = json_decode($event, true);

        if ($this->encodePublish($event) == $event['code']) {
            return json_encode($event['data']);
        }

        return false;
    }
}
