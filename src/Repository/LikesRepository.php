<?php

namespace App\Repository;

use App\Entity\Likes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Likes>
 *
 * @method Likes|null find($id, $lockMode = null, $lockVersion = null)
 * @method Likes|null findOneBy(array $criteria, array $orderBy = null)
 * @method Likes[]    findAll()
 * @method Likes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LikesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Likes::class);
    }

    /**
     * Есть ли лайк юзера у записи
     *
     * @param mixed $likes_collector_id
     *
     * @return array
     */
    public function getIsLiked($likes_collector_id, $user): bool
    {
        return $this->createQueryBuilder('l')
            ->where('l.is_like = 1')
            ->andWhere('l.user = :user')
            ->andWhere('l.likes_collector = :likes_collector_id')
            ->setParameter('user', $user->getId())
            ->setParameter('likes_collector_id', $likes_collector_id)
            ->getQuery()
            ->getOneOrNullResult() ? true : false;
    }
}
