<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Feedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Feedback|null find($id, $lockMode = null, $lockVersion = null)
 * @method Feedback|null findOneBy(array $criteria, array $orderBy = null)
 * @method Feedback[]    findAll()
 * @method Feedback[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackRepository extends ServiceEntityRepository
{
    //пагинация страниц
    public const PAGE_OFFSET = 10;

    public const FEEDBACK_CLOSED = 1;
    public const FEEDBACK_OPENED = 0;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    /**
     * @param array $order_by
     * @param string $search
     *
     * @return [type]
     */
    public function getAdminAllCategories(
        int $status,
        array $order_by,
        int $limit,
        int $offset,
        string $search = "",
        bool $count = false
    ) {
        $qb = $this->createQueryBuilder('f');
        if ($count) {
            $limit = null;
            $offset = null;
            $qb->select('COUNT(f.id) as count');
        } else {
            $qb->select("f");
        }

        if (!empty($limit)) {
            $qb->setMaxResults($limit);
        }

        if (!empty($offset)) {
            $qb->setFirstResult($offset);
        }

        $qb->where('f.status = :status')->setParameter('status', $status);
        $qb->leftJoin(User::class, 'us', 'WITH', 'us.id = f.user');

        switch ($order_by['sort_param']) {
            case 'id':
                $order_by['sort_param'] = "f.id";
                break;
            case 'create_time':
                $order_by['sort_param'] = "f.create_time";
                break;
            case 'update_time':
                $order_by['sort_param'] = "f.update_time";
                break;
            default:
                $order_by['sort_param'] = "f.update_time";
                break;
        }

        if (!empty($search)) {
            $qb->AndWhere(
                $qb->expr()->orX(
                    "f.title LIKE :search OR 
                    FROM_UNIXTIME(f.create_time) LIKE :search OR 
                    us.first_name LIKE :search OR 
                    us.last_name LIKE :search"
                )
            );

            $qb->setParameter('search', '%' . $search . '%');
        }

        if ($count) {
            return $qb->getQuery()->getSingleScalarResult();
        }

        $qb->orderBy($order_by['sort_param'], $order_by['sort_type']);

        return $qb->getQuery()->getResult();
    }
}
