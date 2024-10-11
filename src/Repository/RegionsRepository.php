<?php

namespace App\Repository;

use App\Entity\Regions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Regions|null find($id, $lockMode = null, $lockVersion = null)
 * @method Regions|null findOneBy(array $criteria, array $orderBy = null)
 * @method Regions[]    findAll()
 * @method Regions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Regions::class);
    }

    /**
     * Производим поиск по идентификатору региона
     *
     * @param int $region_id
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findById(int $region_id)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $region_id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
