<?php

namespace App\Repository;

use App\Entity\Tasks;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @extends ServiceEntityRepository<Tasks>
 *
 * @method Tasks|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tasks|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tasks[]    findAll()
 * @method Tasks[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TasksRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tasks::class);
    }

    /**
     * @return Tasks[] Returns an array of Tasks objects
     */
    public function getUserMonthTasks(User $user, $date): array
    {
        $qb = $this->createQueryBuilder('t');
        $end_date = strtotime("+1 month", $date);

        $qb
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->andWhere("t.is_deleted = :is_deleted")
            ->setParameter('is_deleted', 0)
            ->andWhere("t.task_time BETWEEN :date and :end_date")
            ->setParameter('date', $date)
            ->setParameter('end_date', $end_date)
            ->getQuery()
            ->getResult();

        return  $qb->getQuery()->getResult();
    }
}
