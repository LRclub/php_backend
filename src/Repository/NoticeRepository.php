<?php

namespace App\Repository;

use App\Entity\Notice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notice>
 *
 * @method Notice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notice[]    findAll()
 * @method Notice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoticeRepository extends ServiceEntityRepository
{
    public const TYPE_SUCCESS = 'success';
    public const TYPE_ERROR = 'error';
    public const TYPE_WARNING = 'warning';
    public const TYPE_INFO = 'info';
    // Список всех типов уведомлений
    public const NOTICE_TYPES = [
        self::TYPE_SUCCESS,
        self::TYPE_ERROR,
        self::TYPE_INFO,
        self::TYPE_WARNING
    ];

    public const CATEGORY_SYSTEM = 'system';
    public const CATEGORY_PAYMENT = 'payment';
    public const CATEGORY_MATERIALS = 'materials';
    public const CATEGORY_CHAT = 'chat';
    public const CATEGORY_COMMENTS = 'comments';
    public const CATEGORY_STREAM = 'stream';

    public const CATEGORY_TYPES = [
        self::CATEGORY_SYSTEM,
        self::CATEGORY_PAYMENT,
        self::CATEGORY_MATERIALS,
        self::CATEGORY_COMMENTS,
        self::CATEGORY_STREAM
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notice::class);
    }

    public function readAllNotices($user)
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.is_read', true)
            ->where('n.user = :user')
            ->andWhere('n.is_read = 0')
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getResult();
    }

    /**
     * Список непрочитанных уведомлений
     *
     * @param mixed $user
     *
     * @return [type]
     */
    public function getUnreadNotice($user)
    {
        return $this->createQueryBuilder('n')
            ->where('n.is_read = 0')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getResult();
    }

    /**
     * Кол-во непрочитанных уведомлений
     *
     * @param mixed $user
     *
     * @return [type]
     */
    public function getUnreadNoticeCount($user)
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id) as count')
            ->where('n.is_read = 0')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Получить непрочитанное уведомление по id
     *
     * @param int $notice_id
     *
     * @return [type]
     */
    public function getUnreadNoticeById(int $notice_id)
    {
        return $this->createQueryBuilder('n')
            ->where('n.is_read = 0')
            ->andWhere('n.id = :id')
            ->setParameter('id', $notice_id)
            ->getQuery()
            ->getResult();
    }

    public function add(Notice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Notice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
