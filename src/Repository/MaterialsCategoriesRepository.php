<?php

namespace App\Repository;

use App\Entity\MaterialsCategories;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaterialsCategories>
 *
 * @method MaterialsCategories|null find($id, $lockMode = null, $lockVersion = null)
 * @method MaterialsCategories|null findOneBy(array $criteria, array $orderBy = null)
 * @method MaterialsCategories[]    findAll()
 * @method MaterialsCategories[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaterialsCategoriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaterialsCategories::class);
    }

    public function add(MaterialsCategories $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MaterialsCategories $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param array $order_by
     * @param string $search
     *
     * @return [type]
     */
    public function getAllCategories(
        array $order_by,
        string $search = ""
    ) {
        $qb = $this->createQueryBuilder('m');

        $qb = $this->createQueryBuilder('m')
            ->where('m.is_deleted != 1')
            ->andWhere('m.required != 1');

        switch ($order_by['sort_param']) {
            case 'id':
                $order_by['sort_param'] = "m.id";
                break;
            case 'slug':
                $order_by['sort_param'] = "m.slug";
                break;
            case 'name':
                $order_by['sort_param'] = "m.name";
                break;
            default:
                $order_by['sort_param'] = "m.id";
                break;
        }

        if (!empty($search)) {
            $qb->AndWhere(
                $qb->expr()->orX(
                    "m.id LIKE :search OR 
                    m.slug LIKE :search OR 
                    m.name LIKE :search"
                )
            );

            $qb->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy($order_by['sort_param'], $order_by['sort_type']);

        return $qb->getQuery()->getResult();
    }
}
