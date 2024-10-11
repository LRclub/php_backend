<?php

namespace App\Repository;

use App\Entity\ChangeUserPassword;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChangeUserPassword>
 *
 * @method ChangeUserPassword|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChangeUserPassword|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChangeUserPassword[]    findAll()
 * @method ChangeUserPassword[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChangeUserPasswordRepository extends ServiceEntityRepository
{
    //Время существования кода подтверждения (текущий 7 дней)
    public const DELAY_TIME = 604800;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChangeUserPassword::class);
    }

    public function add(ChangeUserPassword $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ChangeUserPassword $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findLastRestore($user)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user->getId())
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
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
        return $this->createQueryBuilder('c')
            ->andWhere('c.code = :code')
            ->andWhere('c.user = :user_id')
            ->andWhere('c.is_confirmed = 0')
            ->andWhere('c.create_time + :delay_time > :current_time')
            ->setParameter('code', $code)
            ->setParameter('user_id', $user_id)
            ->setParameter('delay_time', self::DELAY_TIME)
            ->setParameter('current_time', time())
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return ChangeUserPassword[] Returns an array of ChangeUserPassword objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ChangeUserPassword
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
