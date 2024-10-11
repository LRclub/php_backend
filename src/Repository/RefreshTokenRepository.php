<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RefreshToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method RefreshToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method RefreshToken[]    findAll()
 * @method RefreshToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    /**
     * Производим поиск по идентификатору пользователя и токену
     *
     * @param int $user_id
     * @param $code
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByUserIdAndToken(int $user_id, $token)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.token = :token')
            ->andWhere('e.user = :user_id')
            ->andWhere('e.expiration_time > :time')
            ->setParameter('token', $token)
            ->setParameter('user_id', $user_id)
            ->setParameter('time', time())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
