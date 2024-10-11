<?php

namespace App\AppBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Doctrine\ORM\EntityManagerInterface;
// Services
use App\Services\Comments\CommentsServices;
// Repository
use App\Repository\UserRepository;
use App\Repository\CommentsRepository;

/**
 * [Description CommentsSenderConsumer]
 */
class CommentsSenderConsumer implements ConsumerInterface
{
    private EntityManagerInterface $em;
    private CommentsServices $commentsServices;
    private CommentsRepository $commentsRepository;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $em,
        CommentsServices $commentsServices,
        UserRepository $userRepository,
        CommentsRepository $commentsRepository
    ) {
        $this->em = $em;
        $this->commentsServices = $commentsServices;
        $this->userRepository = $userRepository;
        $this->commentsRepository = $commentsRepository;
        gc_enable();
    }

    /**
     * @var AMQPMessage $msg
     * @return void
     */
    public function execute(AMQPMessage $msg)
    {
        $result = true;
        $comments = json_decode($msg->getBody(), true);
        $comment_id = $comments['comment_id'];
        $action = $comments['action'];

        $comment = $this->commentsRepository->find($comment_id);

        $this->em->detach($comment);

        $this->commentsServices->sendWebSocketChatComment($action, $comment);

        if (!$result) {
            echo 'Ошибка отправки комментария. ID комментария ' . $comment_id . ' Событие: ' . $action . PHP_EOL;
        }

        gc_collect_cycles();
    }
}
