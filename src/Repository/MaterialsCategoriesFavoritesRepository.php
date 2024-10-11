<?php

namespace App\Repository;

use App\Entity\MaterialsCategoriesFavorites;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Services\Materials\MaterialsServices;

class MaterialsCategoriesFavoritesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaterialsCategoriesFavorites::class);
    }

    /**
     * ID последних 5 любимых категорий
     *
     * @param mixed $user
     *
     * @return [type]
     */
    public function lastFavoriteIds($user)
    {
        $result = $this->createQueryBuilder('m')
            ->select('m')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.id', 'DESC')
            ->getQuery()
            ->getResult();

        if (!$result) {
            return [];
        }
        $ids = [];
        foreach ($result as $value) {
            array_push($ids, $value->getCategory()->getId());
        }

        return $ids;
    }
}
