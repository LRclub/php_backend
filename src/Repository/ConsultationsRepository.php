<?php

namespace App\Repository;

use App\Entity\Consultations;
use App\Entity\Specialists;
use App\Entity\SpecialistsCategories;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Consultations>
 *
 * @method Consultations|null find($id, $lockMode = null, $lockVersion = null)
 * @method Consultations|null findOneBy(array $criteria, array $orderBy = null)
 * @method Consultations[]    findAll()
 * @method Consultations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConsultationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consultations::class);
    }

    public function add(Consultations $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Consultations $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array
     */
    public function getCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.is_deleted = 0')
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return array
     */
    public function getCategoriesAdmin($search = ""): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.is_deleted = 0');

        $qb->leftJoin(SpecialistsCategories::class, 'sc', 'WITH', 'c.id = sc.consultation');
        $qb->leftJoin(Specialists::class, 'sp', 'WITH', 'sp.id = sc.specialist');


        if (!empty($search)) {
            $qb->andWhere($qb->expr()->orX(
                "c.id LIKE :search OR 
                c.name LIKE :search OR 
                sp.fio LIKE :search"
            ));

            $qb->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('c.id', 'DESC');
        return $qb->getQuery()->getResult();
    }
}
