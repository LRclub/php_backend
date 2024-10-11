<?php

namespace App\Repository;

use App\Entity\JournalNotes;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JournalNotes>
 *
 * @method JournalNotes|null find($id, $lockMode = null, $lockVersion = null)
 * @method JournalNotes|null findOneBy(array $criteria, array $orderBy = null)
 * @method JournalNotes[]    findAll()
 * @method JournalNotes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JournalNotesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JournalNotes::class);
    }

    public function add(JournalNotes $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(JournalNotes $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function getNotesArray(User $user): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.user = :user')
            ->andWhere('j.is_deleted = 0')
            ->setParameter('user', $user)
            ->orderBy('j.id', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }
}
