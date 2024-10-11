<?php

namespace App\Repository;

use App\Entity\MaterialsStreamAccess;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaterialsStreamAccess>
 *
 * @method MaterialsStreamAccess|null find($id, $lockMode = null, $lockVersion = null)
 * @method MaterialsStreamAccess|null findOneBy(array $criteria, array $orderBy = null)
 * @method MaterialsStreamAccess[]    findAll()
 * @method MaterialsStreamAccess[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaterialsStreamAccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaterialsStreamAccess::class);
    }

    public function add(MaterialsStreamAccess $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MaterialsStreamAccess $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return MaterialsStreamAccess[] Returns an array of MaterialsStreamAccess objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MaterialsStreamAccess
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
