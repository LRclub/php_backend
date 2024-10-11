<?php

namespace App\AppBundle\Topic;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use App\Services\WebSocketMessagesServices;

abstract class AbstractTopic
{
    private WebSocketMessagesServices $webSocketMessagesServices;

    public function __construct(
        WebSocketMessagesServices $webSocketMessagesServices
    ) {
        $this->webSocketMessagesServices = $webSocketMessagesServices;
    }

    /**
     * Пользователь подписался на событие
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     *
     * @return void
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
    {
        if (!$this->webSocketMessagesServices->validateToken($connection)) {
            $this->closeConnection($connection, $topic);
        }
    }

    /**
     * Пользователь отписался от события
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     *
     * @return void
     */
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
    {
        if (!$this->webSocketMessagesServices->validateToken($connection)) {
            $this->closeConnection($connection, $topic);
        }
    }

    /**
     * Публикация события
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @param mixed $event The event data
     * @param array $exclude
     * @param array $eligible
     *
     * @return void
     */
    public function onPublish(
        ConnectionInterface $connection,
        Topic $topic,
        WampRequest $request,
        $event,
        array $exclude,
        array $eligible
    ): void {
        $event = $this->webSocketMessagesServices->validatePublish($event);
        if (!$event) {
            throw new FirewallRejectionException('Access denied');
        }
        $topic->broadcast(['msg' => $event]);
    }

    /**
     * @param ConnectionInterface $connection
     * @param Topic $topic
     *
     * @return [type]
     */
    public function closeConnection(ConnectionInterface &$connection, Topic &$topic)
    {
        $connection->send("401");
        $connection->close();

        throw new FirewallRejectionException('Access denied');
    }
}
