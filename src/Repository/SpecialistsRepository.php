<?php

namespace App\Repository;

use App\Entity\Specialists;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Specialists>
 *
 * @method Specialists|null find($id, $lockMode = null, $lockVersion = null)
 * @method Specialists|null findOneBy(array $criteria, array $orderBy = null)
 * @method Specialists[]    findAll()
 * @method Specialists[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpecialistsRepository extends ServiceEntityRepository
{
    // Пагинация специалистов в админ панели

    public const PAGE_OFFSET = 10;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Specialists::class);
    }


    /**
     * Список специалистов для админ панели
     *
     * @param mixed $page
     * @param mixed $orderBy
     *
     * @return [type]
     */
    public function getAdminSpecialists($page, $order_by, $search, $count = false)
    {
        $limit = self::PAGE_OFFSET;
        $offset = (intval($page - 1)) * self::PAGE_OFFSET;

        $qb = $this->createQueryBuilder('s');

        if ($count) {
            $limit = null;
            $offset = null;
            $qb->select('COUNT(s.id) as count');
        } else {
            $qb->select("s");
        }

        if (!is_null($limit) && !empty($limit)) {
            $qb->setMaxResults($limit);
        }

        if (!is_null($offset) && !empty($offset)) {
            $qb->setFirstResult($offset);
        }

        $qb->where('s.is_deleted = 0');
        if (!empty($search)) {
            $qb->andWhere($qb->expr()->orX("s.fio LIKE :search OR s.email LIKE :search OR s.price LIKE :search
            OR s.experience LIKE :search"));

            $qb->setParameter('search', '%' . $search . '%');
        }

        if ($count) {
            return $qb->getQuery()->getSingleScalarResult();
        }

        // Параметры сортировки
        switch ($order_by['sort_param']) {
            case 'fio':
                $order_by['sort_param'] = "s.fio";
                break;
            case 'price':
                $order_by['sort_param'] = "s.price";
                break;
            case 'is_active':
                $order_by['sort_param'] = "s.is_active";
                break;
            case 'speciality':
                $order_by['sort_param'] = "s.speciality";
                break;
            case 'experience':
                $order_by['sort_param'] = "s.experience";
                break;
            case 'id':
                $order_by['sort_param'] = "s.id";
                break;
            default:
                $order_by['sort_param'] = "s.sort";
                break;
        }

        $qb->orderBy($order_by['sort_param'], $order_by['sort_type']);
        return $qb->getQuery()->getResult();
    }

    /**
     * Список всех активных специалистов (возвращает массив)
     *
     * @return [type]
     */
    public function getAllSpecialistsArray()
    {
        return $this->createQueryBuilder('s')
            ->where('s.is_active = 1')
            ->andWhere('s.is_deleted = 0')
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }
}
