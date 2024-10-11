<?php

namespace App\Repository;

use App\Entity\ChatUnreadCount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatUnreadCount>
 *
 * @method ChatUnreadCount|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatUnreadCount|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatUnreadCount[]    findAll()
 * @method ChatUnreadCount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatUnreadCountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatUnreadCount::class);
    }

    public function add(ChatUnreadCount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ChatUnreadCount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
