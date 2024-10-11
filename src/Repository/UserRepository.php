<?php

namespace App\Repository;

use App\Entity\Materials;
use App\Entity\User;
use App\Entity\Promocodes;
use App\Entity\PromocodesUsed;
use App\Entity\Recosting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    //пагинация страниц
    public const PAGE_OFFSET = 20;

    // Пол
    public const GENDER_MAN = "male";
    public const GENDER_WOMAN = 'female';

    public const FEEDBACK_SEARCH_USER_OFFSET = 10;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Фильтр пользователей для админ панели
     *
     * @param int $page
     * @param string $search
     * @param string $sort
     * @param bool $count
     *
     * @return [type]
     */
    public function adminFindUsers(
        int $page,
        $promocode,
        string $search = null,
        array $order_by,
        bool $count = false
    ) {
        $offset = (intval($page - 1)) * self::PAGE_OFFSET;

        $limit = self::PAGE_OFFSET;
        $qb = $this->createQueryBuilder('u');

        if ($count) {
            $limit = null;
            $offset = null;
            $qb->select('COUNT(u.id) as count');
        } else {
            $qb->select("u, p.code");
        }

        if (!is_null($offset) && !empty($offset)) {
            $qb->setFirstResult($offset);
        }

        if (!is_null($limit) && !empty($limit)) {
            $qb->setMaxResults($limit);
        }

        if (!empty($search)) {
            $qb->where('u.email LIKE :search')
                ->orWhere('u.phone LIKE :search')
                ->orWhere('u.id LIKE :search')
                ->orWhere("u.first_name LIKE :search")
                ->orWhere("u.last_name LIKE :search")
                ->orWhere("CONCAT(u.first_name, ' ', u.last_name) LIKE :search")
                ->orWhere("CONCAT(u.last_name, ' ', u.first_name) LIKE :search")
                ->orWhere('p.code LIKE :search');
            $qb->setParameter('search', '%' . $search . '%');
        }

        $qb->leftJoin(PromocodesUsed::class, 'pr', 'WITH', 'pr.user = u.id');
        $qb->leftJoin(Promocodes::class, 'p', 'WITH', 'p.id = pr.promocode');

        if (!empty($promocode)) {
            $qb->andWhere('pr.promocode = :promocode');
            $qb->setParameter('promocode', $promocode->getId());
        }

        if ($count) {
            return $qb->getQuery()->getSingleScalarResult();
        }

        $qb->orderBy($order_by['sort_param'], $order_by['sort_type']);
        // $qb->groupBy('u.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Used to update phone.
     */
    public function updatePhone(PasswordAuthenticatedUserInterface $user, string $phone): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPhone($phone);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Find by email user
     * @param $email
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByEmail($email)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :val')
            ->setParameter('val', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find by phone user
     *
     * @param $phone
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByPhone($phone)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.phone = :val')
            ->setParameter('val', $phone)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find by ID user
     *
     * @param $user_id
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findById(int $user_id)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id = :val')
            ->setParameter('val', $user_id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find by slug user
     *
     * @param $slug
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findBySlug(string $slug)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Возвращаем приглашенных пользователем юзеров
     *
     * @param int $user_id
     * @return int|mixed[]|string
     */
    public function findInvitedByUserId(int $user_id)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.invited = :val')
            ->setParameter('val', $user_id)
            ->getQuery()
            ->getResult();
    }

    /**
     * Проверка уникальности email, не используется ли кем-то другим
     *
     * @param int $user_id
     * @param string $email
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkUniqueEmail(int $user_id, string $email)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :val')
            ->andWhere('u.id <> :id')
            ->setParameter('val', $email)
            ->setParameter('id', $user_id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Возвращаем список пользователей с пагинацией
     *
     * @param $page
     * @return int|mixed|string
     */
    public function getUserByPages($page)
    {
        $offset = ($page - 1) * self::PAGE_OFFSET;

        return $this->createQueryBuilder('u')
            ->orderBy('u.create_time', 'DESC')
            ->setMaxResults(self::PAGE_OFFSET)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult(Query::HYDRATE_SIMPLEOBJECT);
    }

    /**
     * Возвращаем список пользователей по id
     *
     * @param array $ids
     *
     * @return [type]
     */
    public function getUsersById(array $ids)
    {
        return $this->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * Поиск пользователей для обратной связи
     *
     * @param mixed $search
     *
     * @return [type]
     */
    public function userSearchFeedback($search)
    {
        return $this->createQueryBuilder('u')
            ->select('u.id, u.first_name, u.last_name, u.patronymic_name, u.phone, u.roles')
            ->where("CONCAT(u.first_name, ' ', u.last_name, ' ', u.patronymic_name) LIKE :search")
            ->orWhere('u.first_name LIKE :search')
            ->orWhere('u.last_name LIKE :search')
            ->orWhere('u.patronymic_name LIKE :search')
            ->orWhere('u.phone LIKE :search')
            ->orWhere('u.id LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->setMaxResults(self::FEEDBACK_SEARCH_USER_OFFSET)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Поиск пользователей у которых заканчивается подписка
     * Подтверждена почта
     *
     *
     * @return [type]
     */
    public function findEndingSubscription()
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u, DATEDIFF(FROM_UNIXTIME(u.subscription_end_date) , NOW()) as date_end')
            ->where('u.subscription_end_date IS NOT NULL')
            ->andWhere('u.is_confirmed = 1')
            ->andWhere('DATEDIFF(FROM_UNIXTIME(u.subscription_end_date) , NOW()) IN (1, 3, 7)');

        return $qb->getQuery()->getResult();
    }

    /**
     * Поиск пользователей у которых последний день подписки
     * Подтверждена почта
     *
     *
     * @return [type]
     */
    public function findLastDaySubscription()
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u, DATEDIFF(FROM_UNIXTIME(u.subscription_end_date) , NOW()) as date_end')
            ->where('u.subscription_end_date IS NOT NULL')
            ->andWhere('u.is_confirmed = 1')
            ->andWhere('DATEDIFF(FROM_UNIXTIME(u.subscription_end_date) , NOW()) IN (0)');

        return $qb->getQuery()->getResult();
    }

    /**
     * Поиск пользователей с активной подпиской по дате создания материала
     *
     * @return [type]
     */
    public function getUsersWithActiveSubscription(Materials $material)
    {
        $qb =  $this->createQueryBuilder('u');
        $qb->select("u");

        $qb->where('u.subscription_end_date IS NOT NULL')
            ->andWhere('u.subscription_end_date > :date')
            ->setParameter('date', $material->getCreateTime());

        if ($material->getAccess()) {
            $qb->leftJoin(
                Recosting::class,
                'rc',
                'WITH',
                'rc.user = u.id and rc.id =
             (SELECT MAX(rc2.id) FROM \App\Entity\Recosting as rc2 WHERE rc2.user = rc.user)'
            );
            $qb->andWhere('rc.is_vip = 1');
        }

        return $qb->getQuery()->getResult();
    }
}
