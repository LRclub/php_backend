<?php

namespace App\Repository;

use App\Entity\MaterialsFavorites;
use App\Entity\Materials;
use App\Entity\MaterialsCategories;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Services\Materials\MaterialsServices;
use App\Entity\User;
use App\Repository\RecostingRepository;

/**
 * @extends ServiceEntityRepository<Materials>
 *
 * @method Materials|null find($id, $lockMode = null, $lockVersion = null)
 * @method Materials|null findOneBy(array $criteria, array $orderBy = null)
 * @method Materials[]    findAll()
 * @method Materials[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaterialsRepository extends ServiceEntityRepository
{
    private const FILTER_LIMIT = 10;

    private RecostingRepository $recostingRepository;

    public function __construct(
        ManagerRegistry $registry,
        RecostingRepository $recostingRepository
    ) {
        $this->recostingRepository = $recostingRepository;
        parent::__construct($registry, Materials::class);
    }

    /**
     * Список материалов для юзеров
     *
     * @param User $user
     * @param array $filter
     * @param array $category_ids
     * @param bool $count
     * @param bool $is_favorite
     * @param bool $skip_meditations
     *
     * @return [type]
     */
    public function getMaterials(
        User $user,
        array $filter,
        array $category_ids,
        bool $count = false,
        bool $is_favorite = false,
        bool $is_admin = false,
        bool $skip_meditations = false
    ) {
        $qb = $this->createQueryBuilder('m');

        if ($count) {
            $filter['limit'] = null;
            $filter['offset'] = null;
            $qb->select('COUNT(m.id) as count');
        } else {
            $qb->select("m");
            $qb = $this->getCommentsAndLikes($qb);
        }

        if (!is_null($filter['limit']) && !empty($filter['limit'])) {
            $qb->setMaxResults($filter['limit']);
        }

        if (!is_null($filter['offset']) && !empty($filter['offset'])) {
            $qb->setFirstResult($filter['offset']);
        }

        $qb = $this->getUserAccess($qb, $user);
        if (empty($qb)) {
            return [];
        }

        if (!$is_admin) {
            $qb->AndWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->isNull('m.lazy_post'),
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->lt('m.lazy_post', time()),
                    ),
                )
            );
        }

        if ($is_favorite) {
            $qb->leftJoin(MaterialsFavorites::class, 'fav', 'WITH', 'fav.material = m.id');
            $qb->andWhere('fav.user = :user')->setParameter('user', $user);
        }

        $qb->andWhere('m.is_deleted = 0');

        if ($skip_meditations) {
            $qb->andWhere("m.type != :meditation")
                ->setParameter('meditation', MaterialsServices::TYPE_MEDITATION);
        }

        if ($filter['category_slug']) {
            $qb->leftJoin(MaterialsCategories::class, 'cat', 'WITH', 'cat.id = m.category');
            $qb
                ->andWhere("cat.slug = :category_slug")
                ->setParameter('category_slug', $filter['category_slug']);
        } elseif (!empty($category_ids)) {
            $qb
                ->andWhere("m.category IN (:category_ids)")
                ->setParameter('category_ids', $category_ids);
        } else {
            if (!$is_admin && !$is_favorite) {
                $qb->leftJoin(MaterialsCategories::class, 'cat', 'WITH', 'cat.id = m.category');
                $qb->andWhere("cat.code IS NULL");
            }
        }

        if (!empty($filter['type'])) {
            $qb
                ->andWhere("m.type = :type")
                ->setParameter('type', $filter['type']);
        }

        if (!empty($filter['search'])) {
            $qb->AndWhere(
                $qb->expr()->orX("m.title LIKE :search OR
            m.description LIKE :search OR
            m.short_description LIKE :search")
            );

            $qb->setParameter('search', '%' . $filter['search'] . '%');
        }

        if ($count) {
            return $qb->getQuery()->getSingleScalarResult();
        }

        // Параметры сортировки
        $sort_params = [
            'date' => 'm.create_time',
            'views_count' => 'm.views_count',
            'likes_count' => 'likes_count',
            'comments_count' => 'comments_count',
            'stream' => 'm.stream_start'
        ];

        if (array_key_exists($filter['sort_param'], $sort_params)) {
            $filter['sort_param'] = $sort_params[$filter['sort_param']];
        } else {
            $filter['sort_param'] = 'm.id';
        }

        $qb->orderBy($filter['sort_param'], $filter['sort_type']);
        $qb->groupBy('m.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * Список материалов для афиши
     *
     * @param User $user
     * @param array $order_by
     *
     * @return [type]
     */
    public function getShowBillMaterials(User $user, array $order_by)
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select("m");
        $qb->where('m.is_deleted = 0')
            ->andWhere('m.is_show_bill = 1')
            ->andWhere('m.lazy_post IS NOT NULL')
            ->andWhere('m.lazy_post >= :time')
            ->setParameter('time', time());

        if (!$user->getIsSpecialRole()) {
            $last_recosting = $this->recostingRepository->findOneBy(
                ['user' => $user],
                ['id' => 'DESC'],
                1,
                0
            );

            if (empty($last_recosting)) {
                return [];
            }

            if (!$last_recosting->getIsVip()) {
                $qb->andWhere('m.access = 0');
            }

            $qb->andWhere('m.lazy_post <= :user_sub')
                ->setParameter('user_sub', $user->getSubscriptionEndDate());
        }

        // Параметры сортировки
        switch ($order_by['sort_param']) {
            case 'date':
                $order_by['sort_param'] = "m.lazy_post";
                break;
            default:
                $order_by['sort_param'] = "m.lazy_post";
                break;
        }

        $qb->orderBy($order_by['sort_param'], $order_by['sort_type']);
        $qb->groupBy('m.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * Список материалов без определенных (для главной, если не хватает материалов по избранным категориям)
     *
     * @return [type]
     */
    public function getMaterialsWithoutIds(
        User $user,
        int $limit,
        array $category_ids
    ) {
        $qb = $this->createQueryBuilder('m');

        $qb->select("m");
        $qb = $this->getCommentsAndLikes($qb);


        if (!is_null($limit) && !empty($limit)) {
            $qb->setMaxResults($limit);
        }

        $qb = $this->getUserAccess($qb, $user);
        if (empty($qb)) {
            return [];
        }

        $qb->AndWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->isNull('m.lazy_post'),
                ),
                $qb->expr()->andX(
                    $qb->expr()->lt('m.lazy_post', time()),
                ),
            )
        );

        $qb
            ->andWhere('m.is_deleted = 0')
            ->andWhere("m.id NOT IN (:ids)")
            ->setParameter('ids', $category_ids);

        $qb->andWhere("m.type != :meditation")
            ->setParameter('meditation', MaterialsServices::TYPE_MEDITATION);

        $qb->groupBy('m.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param mixed $material_id
     *
     * @return [type]
     */
    public function getMaterialById(User $user, int $material_id)
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select("m");
        $qb = $this->getCommentsAndLikes($qb);

        $qb = $this->getUserAccess($qb, $user);
        if (empty($qb)) {
            return [];
        }

        $qb
            ->andWhere('m.id = :material_id')
            ->andWhere('m.is_deleted = 0')
            ->setParameter('material_id', $material_id);

        $qb->groupBy('m.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * Список эфиров для главной страницы
     *
     * @return [type]
     */
    public function getStreamsMainPage(User $user)
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select("m");
        $qb = $this->getCommentsAndLikes($qb);

        $qb = $this->getUserAccess($qb, $user);
        if (empty($qb)) {
            return [];
        }

        $qb
            ->andWhere('m.type = :type')
            ->andWhere('m.is_deleted = 0')
            ->setParameter('type', MaterialsServices::TYPE_STREAM);

        $qb->setMaxResults(MaterialsServices::MAIN_PAGE_ITEMS);

        $qb->orderBy('m.stream_start', 'DESC');
        return $qb->getQuery()->getResult();
    }

    /**
     * Список материалов на главную для афиши
     *
     * @return [type]
     */
    public function getBillboardMainPage(User $user)
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select("m");
        $qb = $this->getCommentsAndLikes($qb);

        $qb->setMaxResults(MaterialsServices::MAIN_PAGE_ITEMS);

        $qb = $this->getUserAccess($qb, $user);
        if (empty($qb)) {
            return [];
        }

        $qb->AndWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->isNull('m.lazy_post'),
                ),
                $qb->expr()->andX(
                    $qb->expr()->lt('m.lazy_post', time()),
                ),
            )
        );

        $qb
            ->andWhere('m.is_show_bill = 1')
            ->andWhere('m.is_deleted = 0');

        $qb->andWhere("m.type != :meditation")
            ->setParameter('meditation', MaterialsServices::TYPE_MEDITATION);

        $qb->orderBy('m.stream_start', 'DESC');
        return $qb->getQuery()->getResult();
    }

    /**
     * Получение материалов по избранным категориям
     *
     * @param mixed $user
     *
     * @return [type]
     */
    public function getMaterialsByFavoriteCategory(User $user)
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select("m");
        $qb = $this->getCommentsAndLikes($qb);

        $qb->setMaxResults(MaterialsServices::MAIN_PAGE_ITEMS);

        $qb = $this->getUserAccess($qb, $user);
        if (empty($qb)) {
            return [];
        }

        $qb
            ->andWhere('m.is_show_bill = 1')
            ->andWhere('m.type != :type')
            ->andWhere('m.is_deleted = 0')
            ->setParameter('type', MaterialsServices::TYPE_STREAM);

        $qb->orderBy('m.stream_start', 'DESC');
        return $qb->getQuery()->getResult();
    }

    /**
     * Список материалов для календаря (полный)
     *
     * @return [type]
     */
    public function getCalendarMaterials(
        User $user,
        $date,
        bool $add_meditation = false
    ) {
        $qb = $this->createQueryBuilder('m');
        $end_date = strtotime("+1 month", $date);

        $qb->select("m");

        $qb = $this->getUserAccess($qb, $user);
        if (empty($qb)) {
            return [];
        }

        $qb->andWhere('m.is_deleted = 0');

        if (!$add_meditation) {
            $qb->andWhere("m.type != :meditation")
                ->setParameter('meditation', MaterialsServices::TYPE_MEDITATION);
        }

        $qb->AndWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->isNull('m.lazy_post'),
                    $qb->expr()->between('m.create_time', $date, $end_date),
                ),
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('m.lazy_post'),
                    $qb->expr()->between('m.lazy_post', $date, $end_date),
                ),
            )
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Фильтр материалов
     *
     * @param User $user
     * @param mixed $filter
     *
     * @return [type]
     */
    public function filterMaterials(User $user, string $search)
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select("m");
        $qb = $this->getCommentsAndLikes($qb);
        $qb->setMaxResults(self::FILTER_LIMIT);
        $qb = $this->getUserAccess($qb, $user);
        if (empty($qb)) {
            return [];
        }
        $qb->AndWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->isNull('m.lazy_post'),
                ),
                $qb->expr()->andX(
                    $qb->expr()->lt('m.lazy_post', time()),
                ),
            )
        );

        $qb->andWhere('m.is_deleted = 0');
        if (!empty($search)) {
            $qb->andWhere($qb->expr()->orX("m.id LIKE :search OR m.title LIKE :search OR m.description LIKE :search"));

            $qb->setParameter('search', '%' . $search . '%');
        }

        $qb->groupBy('m.id');
        return $qb->getQuery()->getResult();
    }

    /**
     * Список активных эфиров
     *
     * @return [type]
     */
    public function getActiveStreams()
    {
        $qb = $this->createQueryBuilder('m');

        $qb->andWhere("m.is_stream_finished != 1");
        $qb->andWhere("m.type = :stream")
            ->setParameter('stream', MaterialsServices::TYPE_STREAM);
        $qb->andWhere("m.stream_start <= :date")
            ->setParameter('date', strtotime("+12 hours", time()));

        return $qb->getQuery()->getResult();
    }

    /**
     * Список материалов, которые опубликованы
     *
     * @return [type]
     */
    public function getLazyPublishedMaterials()
    {
        $qb = $this->createQueryBuilder('m');

        $qb->andWhere("m.is_deleted != 1");
        $qb->andWhere("m.lazy_post IS NOT NULL");
        $qb->andWhere("m.is_notification_sended != 1");
        $qb->andWhere("m.lazy_post < :time")
            ->setParameter('time', time());

        return $qb->getQuery()->getResult();
    }

    /**
     * Список эфиров которые начнутся через 6 часов
     *
     * @return [type]
     */
    public function getStreamNotificationsMaterials()
    {
        $qb = $this->createQueryBuilder('m');

        $qb->where("m.is_deleted != 1")
            ->andWhere("m.stream_notification_sended != 1")
            ->andWhere("m.type = :stream")
            ->setParameter('stream', MaterialsServices::TYPE_STREAM);

        $qb->andWhere("m.stream_start < :time")
            ->setParameter('time', strtotime("+6 hours", time()));

        return $qb->getQuery()->getResult();
    }

    /**
     * Условия для получения материалов по оплате. Если null, то ничего не возвращаем
     *
     * @param mixed $qb
     * @param mixed $user
     *
     * @return [type]
     */
    private function getUserAccess($qb, User $user)
    {
        if ($user->getIsSpecialRole()) {
            return $qb;
        }

        //материалы доступные для всех
        $qb->where("m.access = 2");

        $recosting = $this->recostingRepository->findBy(['user' => $user]);
        if (!$recosting) {
            return $qb;
        }

        foreach ($recosting as $key => $recost) {
            $is_vip = $recost->getIsVip();
            $sub_from = $recost->getSubscriptionFrom();
            $sub_to = $recost->getSubscriptionTo();

            $qb->orWhere(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        "((m.create_time BETWEEN :sub_from_$key and :sub_to_$key) and (m.lazy_post is null)
                        and (m.stream_start is null))
                        OR ((m.lazy_post is not null) and (m.lazy_post BETWEEN :sub_from_$key and :sub_to_$key))
                        OR ((m.stream_start is not null) and (m.stream_start BETWEEN :sub_from_$key and :sub_to_$key))"
                    ),
                    empty($is_vip) ? $qb->expr()->eq('m.access', '0') : null
                )
            );

            $qb->setParameter("sub_from_$key", $sub_from)
                ->setParameter("sub_to_$key", $sub_to);
        }

        return $qb;
    }

    /**
     * Join comments + likes
     *
     * @param mixed $qb
     *
     * @return [type]
     */
    private function getCommentsAndLikes($qb)
    {
        $qb->addSelect(
            '(SELECT count(comm.id)
                FROM App\Entity\Comments as comm WHERE comm.comments_collector = m.comments_collector)
                AS comments_count'
        );
        $qb->addSelect(
            '(SELECT count(lk.id)
                FROM App\Entity\Likes as lk WHERE lk.likes_collector = m.likes_collector)
                AS likes_count'
        );

        return $qb;
    }
}
