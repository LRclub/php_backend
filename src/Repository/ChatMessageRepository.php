<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 *
 * @method ChatMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatMessage[]    findAll()
 * @method ChatMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    // Пагинация сообщений в чате
    public const PAGE_OFFSET = 20;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    /**
     * Получение списка сообщений для чата
     *
     * @param mixed $message_id
     * @param mixed $chat_id
     * @param mixed $user
     * @param bool $count
     *
     * @return [type]
     */
    public function getMessages($message_id, $chat_id, $user, $count = false)
    {
        $limit = self::PAGE_OFFSET;

        $qb = $this->createQueryBuilder('c');

        if ($count) {
            $limit = null;
            $qb->select('COUNT(c.id) as count');
        } else {
            $qb->select("c");
        }

        if (!is_null($limit) && !empty($limit)) {
            $qb->setMaxResults($limit);
        }

        $qb->where('c.chat = :chat_id');
        $qb->setParameter('chat_id', $chat_id);
        if (!empty($message_id)) {
            $qb->andWhere('c.id < :message_id');
            $qb->setParameter('message_id', $message_id);
        }

        if ($count) {
            return $qb->getQuery()->getSingleScalarResult();
        }

        $qb->orderBy('c.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Проверка можно ли подгрузить сообщения
     *
     * @param mixed $comment_id
     *
     * @return [type]
     */
    public function canLoadMessages($comment_id, $chat_id): bool
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('COUNT(c.id) as count');
        $qb->where('c.chat = :chat_id');
        $qb->setParameter('chat_id', $chat_id);
        if (!empty($comment_id)) {
            $qb->andWhere('c.id < :comment_id');
            $qb->setParameter('comment_id', $comment_id);
        }

        $qb->orderBy('c.id', 'DESC');
        $count = intval($qb->getQuery()->getSingleScalarResult());

        return $count != 0;
    }

    /**
     * @param mixed $last_message
     *
     * @return [type]
     */
    public function getUnreadMessagesCount($last_message)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('COUNT(c.id) as count')
            ->where('c.chat = :chat_id')
            ->setParameter('chat_id', $last_message->getChat()->getId())
            ->andWhere('c.id > :id')
            ->setParameter('id', $last_message->getLastMessage()->getId())
            ->orderBy('c.id', 'DESC');

        $count = intval($qb->getQuery()->getSingleScalarResult());

        return $count;
    }


    /**
     * Возвращаем крайнее сообщение по чату
     *
     * @param int $chat_id
     * @return ChatMessage|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLastMessageInChat(int $chat_id)
    {
        $qb = $this->createQueryBuilder('c');

        return $qb->andWhere('c.chat = :chat_id')
            ->setParameter('chat_id', $chat_id)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}
