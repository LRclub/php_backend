<?php

namespace App\Repository;

use App\Entity\Consultations;
use App\Entity\Specialists;
use App\Entity\SpecialistsCategories;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SpecialistsCategories>
 *
 * @method SpecialistsCategories|null find($id, $lockMode = null, $lockVersion = null)
 * @method SpecialistsCategories|null findOneBy(array $criteria, array $orderBy = null)
 * @method SpecialistsCategories[]    findAll()
 * @method SpecialistsCategories[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpecialistsCategoriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpecialistsCategories::class);
    }

    /**
     * Список ID специалистов для категории
     *
     * @param Consultations $consultation
     *
     * @return [type]
     */
    public function getSpecialistsByCategory(Consultations $consultation, array $orderBy = [], $limit = null)
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.consultation = :consultation')
            ->setParameter('consultation', $consultation->getId());

        if ($orderBy) {
            $qb->leftJoin(Specialists::class, 'sp', 'WITH', 'sp.id = s.specialist');
            $qb->orderBy('sp.' . $orderBy['sort_param'], $orderBy['sort_type']);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
