<?php

namespace App\AppBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use App\Entity\ChatMessage;
use App\Services\Chat\ChatServices;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ChatMessageRepository;

/**
 * [Description ChatSenderConsumer]
 */
class ChatSenderConsumer implements ConsumerInterface
{
    private ChatServices $chatServices;
    private ChatRepository $chatRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $em;
    private ChatMessageRepository $chatMessageRepository;

    public function __construct(
        ChatServices $chatServices,
        ChatRepository $chatRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        ChatMessageRepository $chatMessageRepository
    ) {
        $this->chatServices = $chatServices;
        $this->userRepository = $userRepository;
        $this->chatRepository = $chatRepository;
        $this->em = $em;
        $this->chatMessageRepository = $chatMessageRepository;
        gc_enable();
    }

    /**
     * @var AMQPMessage $msg
     * @return void
     */
    public function execute(AMQPMessage $msg)
    {
        $message = json_decode($msg->getBody(), true);
        $message_id = $message['message_id'];
        $action = $message['action'];

        $message = $this->chatMessageRepository->find($message_id);
        $this->em->detach($message);

        if ($message) {
            if ($action != ChatServices::CHAT_EVENT_SEND) {
                $chat_id = $message->getChat()->getId();
                $last_message = $this->chatMessageRepository->getLastMessageInChat($chat_id);
                $this->em->detach($last_message);

                $is_last = !is_null($last_message) && $last_message->getId() == $message_id;
            } else
                $is_last = true;


            $this->chatServices->sendWebSocketChatMessage($action, $message, $is_last);
        }


        gc_collect_cycles();
    }
}
