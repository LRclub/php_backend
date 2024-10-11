<?php

namespace App\Repository;

use App\Entity\Promocodes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\PromocodesUsed;

/**
 * @method Promocodes|null find($id, $lockMode = null, $lockVersion = null)
 * @method Promocodes|null findOneBy(array $criteria, array $orderBy = null)
 * @method Promocodes[]    findAll()
 * @method Promocodes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PromocodesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promocodes::class);
    }

    /**
     * Поиск по записи промокода
     *
     * @param $code
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByCode($code)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.code = :val')
            ->setParameter('val', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Поиск по номеру телефона
     *
     * @param string $phone
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByPhone(string $phone)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.phone = :val')
            ->setParameter('val', $phone)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array $order_by
     * @param string $search
     *
     * @return [type]
     */
    public function getPromocodesAdmin(
        array $order_by,
        string $search
    ) {
        $qb = $this->createQueryBuilder('u');
        $qb->select("u, count(pu.id) as used_count");
        $qb->leftJoin(PromocodesUsed::class, 'pu', 'WITH', 'pu.promocode = u.id');
        $qb->where('u.is_deleted != 1')
            ->andWhere('u.owner IS NULL');

        if (!empty($search)) {
            $qb->andWhere($qb->expr()->orX(
                "u.id LIKE :search OR 
                u.amount_used LIKE :search OR 
                u.amount LIKE :search OR
                u.discount_percent LIKE :search OR
                FROM_UNIXTIME(u.start_time) LIKE :search OR
                FROM_UNIXTIME(u.end_time) LIKE :search OR 
                u.code LIKE :search"
            ));

            $qb->setParameter('search', '%' . $search . '%');
        }

        // Параметры сортировки
        switch ($order_by['sort_param']) {
            case 'id':
                $order_by['sort_param'] = "u.id";
                break;
            case 'active':
                $order_by['sort_param'] = "u.is_active";
                break;
            case 'amount':
                $order_by['sort_param'] = "u.amount";
                break;
            case 'amount_used':
                $order_by['sort_param'] = "u.amount_used";
                break;
            case 'discount_percent':
                $order_by['sort_param'] = "u.discount_percent";
                break;
            case 'code':
                $order_by['sort_param'] = "u.code";
                break;
            case 'start_time':
                $order_by['sort_param'] = "u.start_time";
                break;
            case 'end_time':
                $order_by['sort_param'] = "u.end_time";
                break;
            default:
                $order_by['sort_param'] = "u.id";
                break;
        }

        $qb->groupBy('u.id');
        $qb->orderBy($order_by['sort_param'], $order_by['sort_type']);

        return $qb->getQuery()->getResult();
    }
}
