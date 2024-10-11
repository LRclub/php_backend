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
use App\Services\Comments\CommentsServices;
use App\Repository\MaterialsRepository;

/**
 * [Description EventsSenderConsumer]
 */
class EventsSenderConsumer implements ConsumerInterface
{
    private CommentsServices $commentsServices;
    private ChatRepository $chatRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $em;
    private MaterialsRepository $materialsRepository;

    public function __construct(
        CommentsServices $commentsServices,
        ChatRepository $chatRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MaterialsRepository $materialsRepository
    ) {
        $this->commentsServices = $commentsServices;
        $this->userRepository = $userRepository;
        $this->chatRepository = $chatRepository;
        $this->em = $em;
        $this->materialsRepository = $materialsRepository;
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
        $material_id = $event['material_id'];
        $action = $event['action'];

        $material = $this->materialsRepository->find($material_id);
        $this->em->detach($material);
        $this->commentsServices->sendWebSocketEvent($action, $material);

        if (!$result) {
            echo 'Ошибка отправки события. ID материала ' . $material_id . ' Событие: ' . $action . PHP_EOL;
        }

        gc_collect_cycles();
    }
}
