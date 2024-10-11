<?php

namespace App\Repository;

use App\Entity\UserEventHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserEventHistory>
 *
 * @method UserEventHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserEventHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserEventHistory[]    findAll()
 * @method UserEventHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserEventHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserEventHistory::class);
    }

    public function add(UserEventHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserEventHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param mixed $user
     *
     * @return [type]
     */
    public function checkUserAuth($user)
    {
        return $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.completion_time >= :current_day')
            ->andWhere("d.action = 'visit'")
            ->setParameter('user', $user)
            ->setParameter('current_day', strtotime(date("Y-m-d")))
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return UserEventHistory[] Returns an array of UserEventHistory objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserEventHistory
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
