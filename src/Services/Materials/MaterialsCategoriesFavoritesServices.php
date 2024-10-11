<?php

namespace App\Services\Materials;

use App\Entity\MaterialsCategoriesFavorites;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\LogicException;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MaterialsCategoriesRepository;
use App\Repository\CommentsRepository;
use App\Repository\LikesRepository;
use App\Repository\MaterialsCategoriesFavoritesRepository;

class MaterialsCategoriesFavoritesServices
{
    private EntityManagerInterface $em;
    private CommentsRepository $commentsRepository;
    private LikesRepository $likesRepository;

    public function __construct(
        EntityManagerInterface $em,
        MaterialsCategoriesRepository $materialsCategoriesRepository,
        MaterialsCategoriesFavoritesRepository $materialsCategoriesFavoritesRepository,
        CommentsRepository $commentsRepository,
        LikesRepository $likesRepository
    ) {
        $this->em = $em;
        $this->materialsCategoriesRepository = $materialsCategoriesRepository;
        $this->materialsCategoriesFavoritesRepository = $materialsCategoriesFavoritesRepository;
        $this->commentsRepository = $commentsRepository;
        $this->likesRepository = $likesRepository;
    }

    /**
     * Добавление категории в избранное
     *
     * @param mixed $user
     * @param mixed $category_id
     *
     * @return [type]
     */
    public function addCategory($user, $category_id)
    {
        $category = $this->materialsCategoriesRepository->find($category_id);
        if (!$category) {
            throw new LogicException('Категория не найдена!');
        }

        $exist = $this->materialsCategoriesFavoritesRepository->findOneBy([
            'category' => $category->getId(),
            'user' => $user->getId()
        ]);

        if ($category->getIsDeleted()) {
            throw new LogicException('Категория удалена!');
        }

        if ($exist) {
            throw new LogicException('Категория уже добавлена!');
        }

        $materials_categories_favorites = new MaterialsCategoriesFavorites();
        $materials_categories_favorites->setUser($user)->setCategory($category);
        $this->em->persist($materials_categories_favorites);
        $this->em->flush();

        return $materials_categories_favorites;
    }

    /**
     * Удаление категории из избранного
     *
     * @param mixed $user
     * @param mixed $category_id
     *
     * @return [type]
     */
    public function deleteCategory($user, $category_id)
    {
        $category = $this->materialsCategoriesRepository->find($category_id);
        if (!$category) {
            throw new LogicException('Категория не найдена!');
        }

        $materials_categories_favorites = $this->materialsCategoriesFavoritesRepository->findOneBy([
            'category' => $category->getId(),
            'user' => $user->getId()
        ]);

        if (!$materials_categories_favorites) {
            throw new LogicException('Категория не найдена в избранном!');
        }

        $this->em->remove($materials_categories_favorites);
        $this->em->flush();

        return true;
    }

    /**
     * Получение избранных категорий
     *
     * @param mixed $user
     *
     * @return [type]
     */
    public function getFavoriteCategories(UserInterface $user)
    {
        $result = [];
        $categories_favorites = $user->getMaterialsCategoriesFavorites();
        if (!$categories_favorites) {
            return $result;
        }

        foreach ($categories_favorites as $category_favorite) {
            $category = $category_favorite->getCategory();
            if (!$category->getIsDeleted()) {
                $result[] = [
                    'category_id' => $category->getId(),
                    'slug' => $category->getSlug(),
                    'name' => $category->getName(),
                    'parent_id' => $category->getParent() ? $category->getParent()->getId() : null,
                ];
            }
        }

        return $result;
    }
}
