<?php

namespace App\Repository;

use App\Entity\AuthCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AuthCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthCode[]    findAll()
 * @method AuthCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthCode::class);
    }

    /**
     * Возвращаем количество запросов с указанного адреса после указанного времени
     * @param String $ip
     * @param int $time
     * @return int|mixed|string
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countCodeRequests(string $ip, string $phone, int $time)
    {
        return $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->andWhere('a.time > :time')
            ->andWhere('a.IP = :val or a.phone = :phone')
            ->setParameter('val', $ip)
            ->setParameter('time', $time)
            ->setParameter('phone', $phone)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Поиск по номеру кода авторизации
     *
     * @param string $code_id
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCode(string $code_id): ?AuthCode
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id = :val')
            ->setParameter('val', $code_id)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}
