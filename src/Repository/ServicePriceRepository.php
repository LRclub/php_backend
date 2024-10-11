<?php

namespace App\Repository;

use App\Entity\ServicePrice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ServicePrice|null find($id, $lockMode = null, $lockVersion = null)
 * @method ServicePrice|null findOneBy(array $criteria, array $orderBy = null)
 * @method ServicePrice[]    findAll()
 * @method ServicePrice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServicePriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServicePrice::class);
    }
}
