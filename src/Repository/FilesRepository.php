<?php

namespace App\Repository;

use App\Entity\Files;
use App\Entity\User;
use App\Entity\Specialists;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\DBAL\Connection;
use App\Services\File\FileServices;

/**
 * @method Files|null find($id, $lockMode = null, $lockVersion = null)
 * @method Files|null findOneBy(array $criteria, array $orderBy = null)
 * @method Files[]    findAll()
 * @method Files[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FilesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Files::class);
    }

    /**
     * Возвращаем результаты с данными выборки по пользователю и идентификаторам
     *
     * @param User $user
     * @param array $ids
     * @return int|mixed[]|string
     */
    public function findByUserIdAndFileIds(User $user, array $ids)
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.id IN (:file_ids)')
            ->andWhere('e.is_deleted = 0')
            ->setParameter('file_ids', $ids);

        // Проверяем админ права
        if (!$user->getIsSpecialRole()) {
            $qb->andWhere('e.user = :user_id')->setParameter('user_id', $user->getId());
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Возвращаем результаты с данными выборки по пользователю и идентификаторам
     *
     * @param int $user_id
     * @param int $file_id
     *
     * @return Files|null
     */
    public function findByUserIdAndFileId(int $user_id, int $file_id): ?Files
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.id = :file_id')
            ->andWhere('e.user = :user_id')
            ->andWhere('e.is_deleted = 0')
            ->setParameter('file_id', $file_id)
            ->setParameter('user_id', $user_id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param UserInterface $user
     *
     * @return [type]
     */
    public function getAvatar(UserInterface $user)
    {
        return $this->createQueryBuilder('e')
            ->where('e.is_deleted = 0')
            ->andWhere('e.is_active = 1')
            ->andWhere('e.user = :user_id')
            ->andWhere("e.file_type = :file_type")
            ->setParameter('user_id', $user->getId())
            ->setParameter('file_type', FileServices::TYPE_AVATAR)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param UserInterface $user
     *
     * @return [type]
     */
    public function getSpecialistAvatar(Specialists $specialist)
    {
        return $this->createQueryBuilder('e')
            ->where('e.is_deleted = 0')
            ->andWhere('e.is_active = 1')
            ->andWhere('e.specialist = :specialist')
            ->andWhere("e.file_type = :file_type")
            ->setParameter('specialist', $specialist->getId())
            ->setParameter('file_type', FileServices::TYPE_SPECIALIST_AVATAR)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param mixed $user
     * @param mixed $type
     *
     * @return [type]
     */
    public function getFilesByType($user, $type)
    {
        return $this->createQueryBuilder('e')
            ->where('e.is_deleted = 0')
            ->andWhere('e.is_active = 1')
            ->andWhere('e.user = :user_id')
            ->andWhere('e.file_type = :type')
            ->setParameter('user_id', $user->getId())
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param mixed $user
     * @param mixed $type
     *
     * @return [type]
     */
    public function getArrayIdsByType($user, $type)
    {
        $result = [];
        $request = $this->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.is_deleted = 0')
            ->andWhere('e.is_active = 1')
            ->andWhere('e.user = :user_id')
            ->andWhere('e.file_type = :type')
            ->setParameter('user_id', $user->getId())
            ->setParameter('type', $type)
            ->getQuery()
            ->getScalarResult();

        array_walk_recursive($request, function ($item, $key) use (&$result) {
            $result[] = intval($item);
        });

        return $result;
    }

    /**
     * @param array $files_ids
     *
     * @return [type]
     */
    public function setActiveFilesByIds(array $files_ids)
    {
        return $this->createQueryBuilder('e')
            ->update()
            ->set('e.is_active', true)
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $files_ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array $files_ids
     *
     * @return [type]
     */
    public function setUnactiveFilesByIds(array $files_ids)
    {
        return $this->createQueryBuilder('e')
            ->update()
            ->set('e.is_active', 0)
            ->set('e.is_deleted', 1)
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $files_ids, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getResult();
    }

    public function getUniqueFileType()
    {
        $result = [];
        $request = $this->createQueryBuilder('e')
            ->select('e.file_type')
            ->groupBy('e.file_type')
            ->getQuery()
            ->getScalarResult();

        array_walk_recursive($request, function ($item, $key) use (&$result) {
            $result[] = $item;
        });

        return $result;
    }

    public function getAllFilesByType(string $type)
    {
        $result = [];
        $request = $this->createQueryBuilder('e')
            ->select('e.file_path')
            ->where('e.file_type = :type')
            ->setParameter('type', $type)
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getScalarResult();

        array_walk_recursive($request, function ($item, $key) use (&$result) {
            $result[] = $item;
        });

        return $result;
    }
}
