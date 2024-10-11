<?php

namespace App\Repository;

use App\Entity\Tariffs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tariffs>
 *
 * @method Tariffs|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tariffs|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tariffs[]    findAll()
 * @method Tariffs[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TariffsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tariffs::class);
    }

    /**
     * @return [type]
     */
    public function findAllAsArray()
    {
        return $this->createQueryBuilder('t')
            ->where('t.is_active = 1')
            ->orderBy('t.number_months', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function add(Tariffs $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Tariffs $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Получение тарифа по версии и типу
     *
     * @param string $type
     * @param int $version
     *
     * @return array
     */
    public function getTariffVersion(string $type, int $version): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.type = :type')
            ->setParameter('type', $type)
            ->andWhere('t.version = :version')
            ->setParameter('version', $version)
            ->orderBy('t.number_months', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getOtherActiveTariffs(string $type)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.type != :type')
            ->setParameter('type', $type)
            ->andWhere('t.is_active = 1')
            ->orderBy('t.number_months', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
