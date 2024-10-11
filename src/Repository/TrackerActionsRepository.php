<?php

namespace App\Repository;

use App\Entity\TrackerActions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Entity\Tracker;

/**
 * @extends ServiceEntityRepository<TrackerActions>
 *
 * @method TrackerActions|null find($id, $lockMode = null, $lockVersion = null)
 * @method TrackerActions|null findOneBy(array $criteria, array $orderBy = null)
 * @method TrackerActions[]    findAll()
 * @method TrackerActions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrackerActionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackerActions::class);
    }

    public function add(TrackerActions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TrackerActions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Получить задачу за неделю
     *
     * @param mixed $date
     *
     * @return [type]
     */
    public function getWeekActions(User $user, Tracker $task, $date)
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user->getId())
            ->Andwhere('t.action = :action')
            ->setParameter('action', $task->getId())
            ->Andwhere('t.completion_date = :date')
            ->setParameter('date', date('Y-m-d', $date))
            ->orderBy('t.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
