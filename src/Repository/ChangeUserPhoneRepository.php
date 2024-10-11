<?php

namespace App\Repository;

use App\Entity\ChangeUserPhone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ChangeUserPhone|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChangeUserPhone|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChangeUserPhone[]    findAll()
 * @method ChangeUserPhone[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChangeUserPhoneRepository extends ServiceEntityRepository
{
    //Время существования кода подтверждения (текущий 7 дней)
    public const DELAY_TIME = 604800;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChangeUserPhone::class);
    }

    /**
     * Производим поиск по идентификатору пользователя и коду смены номера
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
            ->andWhere('e.create_time + :delay_time > :current_time')
            ->setParameter('code', $code)
            ->setParameter('user_id', $user_id)
            ->setParameter('delay_time', self::DELAY_TIME)
            ->setParameter('current_time', time())
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
