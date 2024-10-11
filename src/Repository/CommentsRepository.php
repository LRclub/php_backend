<?php

namespace App\Repository;

use App\Entity\Comments;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comments>
 *
 * @method Comments|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comments|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comments[]    findAll()
 * @method Comments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentsRepository extends ServiceEntityRepository
{
    // Пагинация комментариев
    public const PAGE_OFFSET = 20;
    // Пагинация ответов на комментарий
    public const REPLY_PAGE_OFFSET = 20;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comments::class);
    }

    /**
     * Получение списка комментариев
     *
     * @param mixed $page
     * @param mixed $collector_id
     * @param mixed $offset
     * @param mixed $limit
     *
     * @return [type]
     */
    public function getComments($comment_id, $collector_id, $limit, $count = false)
    {
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

        $qb->where('c.comments_collector = :collector_id')
            ->andWhere('c.moderation_status = 0')
            ->andWhere('c.reply IS NULL');
        $qb->setParameter('collector_id', $collector_id);

        if ($comment_id) {
            $qb->andWhere('c.id < :comment_id');
            $qb->setParameter('comment_id', $comment_id);
        }

        if ($count) {
            return $qb->getQuery()->getSingleScalarResult();
        }

        $qb->orderBy('c.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Список комментариев для админ панели
     *
     * @param int $is_deleted
     * @param array $filter_params
     * @param bool $count
     *
     * @return [type]
     */
    public function getAdminComments(int $is_deleted, array $filter_params, $count = false)
    {
        $qb = $this->createQueryBuilder('c');

        if ($count) {
            $filter_params['limit'] = null;
            $qb->select('COUNT(c.id) as count');
        } else {
            $qb->select("c");
        }

        if (!empty($filter_params['limit'])) {
            $qb->setMaxResults($filter_params['limit']);
        }

        if (!empty($filter_params['offset'])) {
            $qb->setFirstResult($filter_params['offset']);
        }

        $qb->where('c.is_deleted = :status');
        $qb->setParameter('status', $is_deleted);


        if (!empty($filter_params['search'])) {
            $qb->leftJoin(User::class, 'us', 'WITH', 'us.id = c.user');
            $qb->andWhere($qb->expr()->orX(
                "c.text LIKE :search OR 
                us.first_name LIKE :search OR 
                us.last_name LIKE :search"
            ));

            $qb->setParameter('search', '%' . $filter_params['search'] . '%');
        }

        if ($count) {
            return $qb->getQuery()->getSingleScalarResult();
        }

        // Параметры сортировки
        switch ($filter_params['sort_param']) {
            case 'date':
                $filter_params['sort_param'] = "c.create_time";
                break;
            default:
                $filter_params['sort_param'] = "c.create_time";
                break;
        }

        $qb->orderBy($filter_params['sort_param'], $filter_params['sort_type']);
        return $qb->getQuery()->getResult();
    }

    /**
     * Получение списка ответов
     *
     * @param mixed $page
     * @param mixed $collector_id
     * @param mixed $offset
     * @param mixed $limit
     *
     * @return [type]
     */
    public function getReply($last_comment_id, $collector_id, $comment_id, $limit, $count = false)
    {
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

        $qb->where('c.comments_collector = :collector_id')
            ->andWhere('c.moderation_status = 0')
            ->andWhere('c.reply = :comment_id');
        $qb->setParameter('collector_id', $collector_id);
        $qb->setParameter('comment_id', $comment_id);

        if ($last_comment_id) {
            $qb->andWhere('c.id < :last_comment_id');
            $qb->setParameter('last_comment_id', $last_comment_id);
        }

        if ($count) {
            return $qb->getQuery()->getSingleScalarResult();
        }

        $qb->orderBy('c.id', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
