<?php

namespace App\AppBundle\Topic;

use App\Services\User\UserServices;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use App\Services\WebSocketMessagesServices;
use App\Services\Chat\ChatServices;
use Gos\Bundle\WebSocketBundle\Server\Type\WebSocketServer;

class ChatTopic extends AbstractTopic implements TopicInterface
{
    private WebSocketMessagesServices $webSocketMessagesServices;
    private ChatServices $chatServices;
    private UserServices $userServices;

    private $user_ids = [];

    public function __construct(
        WebSocketMessagesServices $webSocketMessagesServices,
        ChatServices $chatServices,
        UserServices $userServices
    ) {
        $this->webSocketMessagesServices = $webSocketMessagesServices;
        $this->chatServices = $chatServices;
        $this->userServices = $userServices;
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

        $user = $this->webSocketMessagesServices->getUserByConnection($connection);

        $chat_id = $request->getAttributes()->get('chat_id');
        $action = $request->getAttributes()->get('action');

        echo "[" . date("d.m.Y H:i:s") . "]"
            . "Subscribed UserID: " . $user->getId()
            . " Action: " . $action
            . ' ChatID: ' . $chat_id
            . "\r\n";

        $chat = $this->chatServices->getChat($chat_id);
        $user_is_vip = $this->userServices->userIsVip($user);

        if (!$user->getIsSpecialRole() && (!$chat || ($chat->isVip() && !$user_is_vip))) {
            echo "UserID: " . $user->getId() . " doesn't have VIP access! User has been disconnected!\r\n";
            $this->closeConnection($connection, $topic);
        }

        $this->user_ids[$user->getId()] = $user->getFirstName();
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

        $user = $this->webSocketMessagesServices->getUserByConnection($connection);
        unset($this->user_ids[$user->getId()]);
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
            $connection->close();
            throw new FirewallRejectionException('Access denied');
        }

        $message = json_decode($event, true);
        if ($message['action'] != WebSocketMessagesServices::CHAT_LISTEN) {
            $this->chatServices->updateLastUnreadMessage($message['id'], $message['chat_id'], $this->user_ids);
        }

        $topic->broadcast(['msg' => $event]);
    }

    /**
     * Название события
     *
     * @return string
     */
    public function getName(): string
    {
        return 'app.topic.chat';
    }
}
