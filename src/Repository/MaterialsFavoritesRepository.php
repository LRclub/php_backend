<?php

namespace App\Repository;

use App\Entity\MaterialsFavorites;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaterialsFavorites>
 *
 * @method MaterialsFavorites|null find($id, $lockMode = null, $lockVersion = null)
 * @method MaterialsFavorites|null findOneBy(array $criteria, array $orderBy = null)
 * @method MaterialsFavorites[]    findAll()
 * @method MaterialsFavorites[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaterialsFavoritesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaterialsFavorites::class);
    }

    public function add(MaterialsFavorites $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MaterialsFavorites $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return MaterialsFavorites[] Returns an array of MaterialsFavorites objects
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

//    public function findOneBySomeField($value): ?MaterialsFavorites
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
