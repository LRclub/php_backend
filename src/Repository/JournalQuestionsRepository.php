<?php

namespace App\Repository;

use App\Entity\JournalQuestions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JournalQuestions>
 *
 * @method JournalQuestions|null find($id, $lockMode = null, $lockVersion = null)
 * @method JournalQuestions|null findOneBy(array $criteria, array $orderBy = null)
 * @method JournalQuestions[]    findAll()
 * @method JournalQuestions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JournalQuestionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JournalQuestions::class);
    }

    public function add(JournalQuestions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(JournalQuestions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function markAsDeleted(JournalQuestions $entity)
    {
        $entity->setIsDeleted(true);

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * Список вопросов
     *
     * @return array
     */
    public function getQuestions(): array
    {
        return $this->createQueryBuilder('j')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param string $type
     * @return int|mixed[]|string
     */
    public function getQuestionsByType(string $type)
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.type = :type')
            ->andWhere('j.is_deleted is NULL')
            ->setParameter('type', $type)
            ->orderBy('j.sort', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Поиск вопроса по id
     *
     * @return array
     */
    public function getQuestion($id)
    {
        return $this->createQueryBuilder('j')
            ->where('j.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();
    }
}
