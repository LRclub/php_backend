<?php

namespace App\AppBundle\Topic;

use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use App\Services\WebSocketMessagesServices;
use App\Services\User\TokenServices;
use App\AppBundle\Topic\AbstractTopic;

class CommentsTopic extends AbstractTopic implements TopicInterface
{
    /**
     * Название события
     *
     * @return string
     */
    public function getName(): string
    {
        return 'app.topic.comments';
    }
}
