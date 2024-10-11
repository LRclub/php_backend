<?php

namespace App\AppBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use App\Entity\ChatMessage;
use App\Services\User\FeedbackServices;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
use App\Repository\FeedbackMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\Comments\CommentsServices;
use App\Repository\FeedbackRepository;

/**
 * [Description FeedbackSenderConsumer]
 */
class FeedbackSenderConsumer implements ConsumerInterface
{
    private FeedbackServices $feedbackServices;
    private FeedbackMessageRepository $feedbackMessageRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $em;
    private FeedbackRepository $feedbackRepository;

    public function __construct(
        FeedbackServices $feedbackServices,
        FeedbackMessageRepository $feedbackMessageRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        FeedbackRepository $feedbackRepository
    ) {
        $this->feedbackServices = $feedbackServices;
        $this->userRepository = $userRepository;
        $this->feedbackMessageRepository = $feedbackMessageRepository;
        $this->em = $em;
        $this->feedbackRepository = $feedbackRepository;
        gc_enable();
    }

    /**
     * @var AMQPMessage $msg
     * @return void
     */
    public function execute(AMQPMessage $msg)
    {
        $result = true;
        $event = json_decode($msg->getBody(), true);
        $message_id = $event['message_id'];
        $action = $event['action'];

        $feedback_message = $this->feedbackMessageRepository->find($message_id);
        $this->em->detach($feedback_message);
        $this->feedbackServices->sendWebSocketFeedbackMessage($action, $feedback_message);

        if (!$result) {
            echo 'Ошибка отправки события. ID сообщения ' . $message_id . ' Событие: ' . $action . PHP_EOL;
        }

        gc_collect_cycles();
    }
}
