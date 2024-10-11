<?php

namespace App\Repository;

use App\Entity\ServiceWorkTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ServiceWorkTime|null find($id, $lockMode = null, $lockVersion = null)
 * @method ServiceWorkTime|null findOneBy(array $criteria, array $orderBy = null)
 * @method ServiceWorkTime[]    findAll()
 * @method ServiceWorkTime[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiceWorkTimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceWorkTime::class);
    }

    // /**
    //  * @return ServiceWorkTime[] Returns an array of ServiceWorkTime objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ServiceWorkTime
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
