<?php

namespace App\Repository;

use App\Entity\FormattedVideo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormattedVideo>
 *
 * @method FormattedVideo|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormattedVideo|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormattedVideo[]    findAll()
 * @method FormattedVideo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormattedVideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormattedVideo::class);
    }

    public function add(FormattedVideo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FormattedVideo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
