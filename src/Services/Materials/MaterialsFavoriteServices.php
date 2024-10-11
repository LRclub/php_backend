<?php

namespace App\Services\Materials;

use App\Entity\MaterialsFavorites;
use Symfony\Component\Security\Core\Exception\LogicException;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MaterialsRepository;
use App\Repository\MaterialsFavoritesRepository;
use App\Repository\CommentsRepository;
use App\Repository\LikesRepository;
use App\Services\Materials\MaterialsServices;

class MaterialsFavoriteServices
{
    private EntityManagerInterface $em;
    private MaterialsRepository $materialsRepository;
    private MaterialsFavoritesRepository $materialsFavoritesRepository;
    private CommentsRepository $commentsRepository;
    private LikesRepository $likesRepository;
    private MaterialsServices $materialsServices;

    public function __construct(
        EntityManagerInterface $em,
        MaterialsRepository $materialsRepository,
        MaterialsFavoritesRepository $materialsFavoritesRepository,
        CommentsRepository $commentsRepository,
        LikesRepository $likesRepository,
        MaterialsServices $materialsServices
    ) {
        $this->em = $em;
        $this->materialsRepository = $materialsRepository;
        $this->materialsFavoritesRepository = $materialsFavoritesRepository;
        $this->commentsRepository = $commentsRepository;
        $this->likesRepository = $likesRepository;
        $this->materialsServices = $materialsServices;
    }

    /**
     * Добавление материала в избранное
     *
     * @param mixed $user
     * @param mixed $material_id
     *
     * @return [type]
     */
    public function addMaterial($user, $material_id)
    {
        $material = $this->materialsRepository->find($material_id);
        if (!$material) {
            throw new LogicException('Материал не найден!');
        }

        $exist = $this->materialsFavoritesRepository->findOneBy([
            'material' => $material->getId(),
            'user' => $user->getId()
        ]);

        if ($material->getIsDeleted()) {
            throw new LogicException('Материал удален!');
        }

        if ($exist) {
            throw new LogicException('Материал уже добавлен!');
        }

        $materials_favorites = new MaterialsFavorites();
        $materials_favorites->setUser($user)->setMaterial($material);
        $this->em->persist($materials_favorites);
        $this->em->flush();

        return $materials_favorites;
    }

    /**
     * Удаление материала из избранного
     *
     * @param mixed $user
     * @param mixed $material_id
     *
     * @return [type]
     */
    public function deleteMaterial($user, $material_id)
    {
        $material = $this->materialsRepository->find($material_id);
        if (!$material) {
            throw new LogicException('Материал не найден!');
        }

        $materials_favorites = $this->materialsFavoritesRepository->findOneBy([
            'material' => $material->getId(),
            'user' => $user->getId()
        ]);

        if (!$materials_favorites) {
            throw new LogicException('Материал не найден в избранном!');
        }

        $this->em->remove($materials_favorites);
        $this->em->flush();

        return true;
    }
}
