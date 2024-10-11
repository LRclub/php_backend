<?php

namespace App\Repository;

use App\Entity\Feedback;
use App\Entity\FeedbackMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FeedbackMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackMessage[]    findAll()
 * @method FeedbackMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackMessageRepository extends ServiceEntityRepository
{
    // Период не прочитанных сообщений (текущее 10 минут)
    public const TIME_OFFSET = 10 * 60;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackMessage::class);
    }

    /**
     * @param mixed $value
     *
     * @return [type]
     */
    public function findUnreadRequestMessages($feedback, $user)
    {
        return $this->createQueryBuilder('m')
            ->where('m.user != :user')
            ->andWhere('m.feedback = :feedback')
            ->andWhere('m.is_deleted = 0')
            ->setParameter('user', $user)
            ->setParameter('feedback', $feedback)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param mixed $value
     *
     * @return [type]
     */
    public function findUnreadFeedbackMessages($feedback, $user)
    {
        return $this->createQueryBuilder('m')
            ->where('m.user != :user')
            ->andWhere('m.feedback = :feedback')
            ->andWhere('m.is_read = 0')
            ->andWhere('m.is_deleted = 0')
            ->setParameter('user', $user)
            ->setParameter('feedback', $feedback)
            ->getQuery()
            ->getResult();
    }

    /**
     * Поиск сообщений, которые не читали, для отправки уведомления
     *
     * @return [type]
     */
    public function notificationUnreadMesages()
    {
        return $this->createQueryBuilder('m')
            ->where('m.is_read = 0')
            ->andWhere('m.create_time + ' . self::TIME_OFFSET . ' < :time')
            ->andWhere('m.notification_sended = 0')
            ->andWhere('m.is_deleted = 0')
            ->setParameter('time', time())
            ->getQuery()
            ->getResult();
    }
}
