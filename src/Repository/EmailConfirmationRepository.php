<?php

namespace App\Repository;

use App\Entity\EmailConfirmation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EmailConfirmation|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailConfirmation|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailConfirmation[]    findAll()
 * @method EmailConfirmation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailConfirmationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailConfirmation::class);
    }

    /**
     * Производим поиск по идентификатору пользователя и коду активации
     *
     * @param int $user_id
     * @param $code
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByUserIdAndCode(int $user_id, $code)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.code = :code')
            ->andWhere('e.user = :user_id')
            ->andWhere('e.is_confirmed = 0')
            ->setParameter('code', $code)
            ->setParameter('user_id', $user_id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Дергаем крайний запрос на ресенд
     *
     * @param int $user_id
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByUserId(int $user_id)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :user_id')
            ->andWhere('e.is_confirmed = 0')
            ->orderBy('e.create_time', 'DESC')
            ->setParameter('user_id', $user_id)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}
