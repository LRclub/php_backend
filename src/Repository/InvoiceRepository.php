<?php

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

/**
 * @method Invoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Invoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Invoice[]    findAll()
 * @method Invoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceRepository extends ServiceEntityRepository
{
    //пагинация страниц
    public const PAGE_OFFSET = 10;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    // /**
    //  * @return Invoice[] Returns an array of Invoice objects
    //  */
    public function adminGetPayment(int $page, string $search, array $order_by, array $status, bool $count = false)
    {
        $offset = (intval($page - 1)) * self::PAGE_OFFSET;
        $limit = self::PAGE_OFFSET;
        // Задаем сразу ограничения.
        $range = " LIMIT $limit OFFSET $offset";
        if ($count) {
            $range = "";
        }

        $sort = ' ORDER BY ' . $order_by['sort_param'] . ' ' . $order_by['sort_type'];

        $parameters['status'] = $status;
        $types['status'] = Connection::PARAM_INT_ARRAY;
        $sql_where = "";
        // Создаем подключение
        $conn = $this->getEntityManager()->getConnection();

        // Поисковая сторока
        if (!empty(trim($search))) {
            $parameters['search'] = '%' . $search . '%';
            $sql_where .= '(FROM_UNIXTIME(invoice.create_time) LIKE :search OR invoice.user_id LIKE 
            :search OR invoice.id LIKE :search OR CONCAT(user.first_name, " ", user.last_name, " ", 
            user.patronymic_name) LIKE :search) and';
        }

        // Выборка данных
        $query = "`invoice`LEFT JOIN subscription_history on subscription_history.invoice_id = invoice.id 
        and subscription_history.type = 'pay'
        JOIN user on user.id = invoice.user_id
        WHERE $sql_where invoice.status IN (:status)";

        if ($count) {
            // Выборка count
            $select = trim("SELECT COUNT(*) as 'count' FROM " . $query);
            return  intval($conn->executeQuery($select, $parameters, $types)->fetch()['count']);
        } else {
            $select = trim("SELECT invoice.*, user.first_name, user.last_name, user.patronymic_name, 
            subscription_history.description, CONCAT(user.first_name, ' ', user.last_name, ' ', user.patronymic_name)
            as fio FROM " . $query . $sort . $range);
            return $conn->executeQuery($select, $parameters, $types)->fetchAll();
        }
    }


    /*
    public function findOneBySomeField($value): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
