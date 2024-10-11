<?php

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\SubscriptionHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Services\Payment\PaymentServices;

/**
 * @method SubscriptionHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubscriptionHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubscriptionHistory[]    findAll()
 * @method SubscriptionHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionHistory::class);
    }

    /**
     * @param mixed $value
     *
     * @return [type]
     */
    public function findUserHistory(User $user)
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user->getId())
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Получение последней подписки
     *
     * @param mixed $user
     * @param bool $is_recurring
     *
     * @return SubscriptionHistory|null
     */
    public function getLastUserSubscription($user, bool $is_recurring = false): ?SubscriptionHistory
    {
        $qb = $this->createQueryBuilder('s');

        $qb->where('s.user = :user')
            ->setParameter('user', $user);

        $qb->leftJoin(Invoice::class, 'inv', 'WITH', 'inv.id = s.invoice');
        // Тип оплаты = подписка
        $qb->andWhere('inv.type = :type')
            ->setParameter('type', PaymentServices::TYPE_SUBSCRIPTION)
            // Последний платеж успешно прошел
            ->andWhere('inv.status = :status')
            ->setParameter('status', PaymentServices::ORDER_PAID);
        if ($is_recurring) {
            // Смотрим что бы invoice был создан с флагом recurrent = true и флаг списания был включен
            $qb->andWhere('inv.is_recurring = :is_recurring')->setParameter('is_recurring', true);
            $qb->andWhere('inv.is_auto = :is_auto')->setParameter('is_auto', false);
        }

        $qb->orderBy('s.id', 'DESC');

        return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param array $order_by
     * @param string $search
     * @param bool $count
     *
     * @return [type]
     */
    public function getAdminPaymentHistory(
        int $limit,
        int $offset,
        array $order_by,
        string $search,
        bool $count = false
    ) {
        $qb = $this->createQueryBuilder('s');

        if ($count) {
            $limit = null;
            $offset = null;
            $qb->select('COUNT(s.id) as count');
        } else {
            $qb->select("s");
        }

        if (!empty($limit)) {
            $qb->setMaxResults($limit);
        }

        if (!empty($offset)) {
            $qb->setFirstResult($offset);
        }

        if (!empty($search)) {
            $qb->leftJoin(User::class, 'us', 'WITH', 'us.id = s.user');
            $qb->andWhere($qb->expr()->orX(
                "s.id LIKE :search OR 
                s.price LIKE :search OR
                FROM_UNIXTIME(s.create_time, '%d.%m.%Y %h:%i') LIKE :search OR
                us.id LIKE :search OR
                us.first_name LIKE :search OR
                us.last_name LIKE :search OR
                us.patronymic_name LIKE :search"
            ));
            $qb->setParameter('search', '%' . $search . '%');
        }

        if ($count) {
            return $qb->getQuery()->getSingleScalarResult();
        }

        // Параметры сортировки
        switch ($order_by['sort_param']) {
            case 'id':
                $order_by['sort_param'] = "s.id";
                break;
            case 'date':
                $order_by['sort_param'] = "s.create_time";
                break;
            case 'price':
                $order_by['sort_param'] = "s.price";
                break;
            default:
                $order_by['sort_param'] = "s.id";
                break;
        }

        $qb->orderBy($order_by['sort_param'], $order_by['sort_type']);

        return $qb->getQuery()->getResult();
    }
}
