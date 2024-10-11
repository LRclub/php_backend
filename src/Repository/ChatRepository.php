<?php

namespace App\Repository;

use App\Entity\Chat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Chat>
 *
 * @method Chat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Chat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Chat[]    findAll()
 * @method Chat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatRepository extends ServiceEntityRepository
{
    // Публичный чат для всех пользователей
    public const TYPE_PUBLIC = 'public';
    // Чат с куратором
    public const TYPE_CURATOR = 'curator';
    // Чат тематический чат
    public const TYPE_THEMATIC = 'thematic';

    // Доступные типы чатов
    public const CHAT_TYPES = [
        self::TYPE_PUBLIC, self::TYPE_CURATOR, self::TYPE_THEMATIC
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat::class);
    }

    /**
     * Получить список доступных чатов
     *
     * @param mixed $value
     *
     * @return array
     */
    public function getChatList($user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.type IN (:types)')
            ->orWhere('c.first_user = :user')
            ->setParameter('types', [self::TYPE_PUBLIC, self::TYPE_THEMATIC])
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getResult();
    }
}
