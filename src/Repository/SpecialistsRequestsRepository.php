<?php

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\SpecialistsRequests;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Services\Payment\PaymentServices;

/**
 * @extends ServiceEntityRepository<SpecialistsRequests>
 *
 * @method SpecialistsRequests|null find($id, $lockMode = null, $lockVersion = null)
 * @method SpecialistsRequests|null findOneBy(array $criteria, array $orderBy = null)
 * @method SpecialistsRequests[]    findAll()
 * @method SpecialistsRequests[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpecialistsRequestsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpecialistsRequests::class);
    }

    public function add(SpecialistsRequests $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SpecialistsRequests $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param mixed $page
     *
     * @return array
     */
    public function getRequests($limit, $offset, $count = false)
    {
        $qb = $this->createQueryBuilder('s');

        if ($count) {
            $limit = null;
            $offset = null;
            $qb->select('COUNT(s.id) as count');
        } else {
            $qb->select("s");
        }

        if (!is_null($limit) && !empty($limit)) {
            $qb->setMaxResults($limit);
        }

        if (!is_null($offset) && !empty($offset)) {
            $qb->setFirstResult($offset);
        }

        $qb->leftJoin(Invoice::class, 'inv', 'WITH', 'inv.id = s.invoice');
        $qb->where('inv.status = :status')
            ->andWhere('inv.type = :type')
            ->setParameter('status', PaymentServices::ORDER_PAID)
            ->setParameter('type', PaymentServices::TYPE_CONSULTATION);

        $qb->orderBy('s.id', 'desc');

        if ($count) {
            return $qb->getQuery()->getSingleScalarResult();
        }

        return $qb->getQuery()->getResult();
    }
}
