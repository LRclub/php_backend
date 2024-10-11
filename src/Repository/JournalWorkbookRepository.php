<?php

namespace App\Repository;

use App\Entity\JournalWorkbook;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JournalWorkbook>
 *
 * @method JournalWorkbook|null find($id, $lockMode = null, $lockVersion = null)
 * @method JournalWorkbook|null findOneBy(array $criteria, array $orderBy = null)
 * @method JournalWorkbook[]    findAll()
 * @method JournalWorkbook[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JournalWorkbookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JournalWorkbook::class);
    }

    public function add(JournalWorkbook $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(JournalWorkbook $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param User $user
     * @param object $date
     * @param string $type
     *
     * @return [type]
     */
    public function findUserWorkbook(User $user, string $date, string $type)
    {
        $qb = $this->createQueryBuilder('j')
            ->where('j.user = :user')
            ->setParameter('user', $user->getId())
            ->setMaxResults(1);

        $qb->andWhere("j.date = :date")
            ->setParameter("date", $date);

        $qb->andWhere("j.type = :type")
            ->setParameter("type", $type);

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param User $user
     * @param string $date
     *
     * @return [type]
     */
    public function findArrayWorkbook(User $user, string $date, string $type)
    {
        $qb = $this->createQueryBuilder('j')
            ->where('j.user = :user')
            ->setParameter('user', $user->getId())
            ->setMaxResults(1);

        $qb->andWhere("j.date = :date")
            ->setParameter("date", $date);

        $qb->andWhere("j.type = :type")
            ->setParameter("type", $type);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param User $user
     * @param string $date
     *
     * @return [type]
     */
    public function findWorkbookWeek(User $user, array $date)
    {
        $qb = $this->createQueryBuilder('j')
            ->where('j.user = :user')
            ->setParameter('user', $user->getId());

        $qb->andWhere("j.date BETWEEN :date_from and :date_to")
            ->setParameter("date_from", $date['date_from'])
            ->setParameter("date_to", $date['date_to']);

        $qb->andWhere("j.type = :type")
            ->setParameter("type", 'week');

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
