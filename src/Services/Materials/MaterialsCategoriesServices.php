<?php

namespace App\Services\Materials;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use App\Repository\MaterialsRepository;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Repository\MaterialsCategoriesRepository;
use App\Entity\MaterialsCategories;
use App\Repository\MaterialsCategoriesFavoritesRepository;
use GrumPHP\Util\Str;

class MaterialsCategoriesServices
{
    private EntityManagerInterface $em;
    private MaterialsRepository $materialsRepository;
    private MaterialsCategoriesRepository $materialsCategoriesRepository;
    private MaterialsCategoriesFavoritesRepository $materialsCategoriesFavoritesRepository;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $em,
        UserRepository $userRepository,
        MaterialsRepository $materialsRepository,
        MaterialsCategoriesRepository $materialsCategoriesRepository,
        MaterialsCategoriesFavoritesRepository $materialsCategoriesFavoritesRepository
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->materialsRepository = $materialsRepository;
        $this->materialsCategoriesRepository = $materialsCategoriesRepository;
        $this->materialsCategoriesFavoritesRepository = $materialsCategoriesFavoritesRepository;
    }

    /**
     * Создание категории
     *
     * @param mixed $form
     *
     * @return [type]
     */
    public function addCategory($user, $form)
    {
        $slug = $form->get('slug')->getData();
        $name = $form->get('name')->getData();
        $parent = $form->get('parent_id')->getData();

        $materials_categories = new MaterialsCategories();
        $materials_categories
            ->setName($name)
            ->setSlug($slug)
            ->setParent($parent);
        $this->em->persist($materials_categories);
        $this->em->flush();

        return $materials_categories;
    }

    /**
     * Редактирование категории
     *
     * @param mixed $form
     *
     * @return [type]
     */
    public function editCategory($user, $form)
    {
        $category = $form->get('category_id')->getData();
        $name = $form->get('name')->getData();
        $parent = $form->get('parent_id')->getData();

        $category->setName($name)->setParent($parent);
        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    /**
     * Удаление категории
     *
     * @param mixed $form
     *
     * @return [type]
     */
    public function deleteCategory($user, $category_id)
    {
        $category = $this->materialsCategoriesRepository->find($category_id);
        if (empty($category)) {
            throw new LogicException('Категория не найдена');
        }

        if ($category->getIsDeleted()) {
            throw new LogicException('Категория удалена');
        }

        // Если категория родительская
        if (!$category->getParent()) {
            throw new LogicException('Нельзя удалить родительскую категорию');
        }

        $materials = $this->materialsRepository->findBy(['category' => $category->getId()]);
        if ($materials) {
            foreach ($materials as $material) {
                $material->setCategory($category->getParent());
                $this->em->persist($material);
                $this->em->flush();
            }
        }

        $category->setIsDeleted(true);
        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    /**
     * Список родительский категорий
     *
     * @return [type]
     */
    public function getParentCategories()
    {
        $result = [];
        $categories = $this->materialsCategoriesRepository->findBy([
            'parent' => null,
            'is_deleted' => false
        ]);
        if (!$categories) {
            return $result;
        }

        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->getId(),
                'name' => $category->getName()
            ];
        }

        return $result;
    }

    /**
     * Список всех категорий
     *
     * @param array $order_by
     * @param string $search
     *
     * @return [type]
     */
    public function getAllCategories(
        array $order_by,
        string $search = ""
    ) {
        $result = [];
        $categories = $this->materialsCategoriesRepository->getAllCategories($order_by, $search);

        if (!$categories) {
            return $result;
        }

        foreach ($categories as $category) {
            $result[] = $this->getCategoryInfo($category);
        }

        return $result;
    }

    /**
     * Древовидная структура для вывода многоуровневого меню категорий
     *
     * @return [type]
     */
    public function getMenuCategories($user)
    {
        $result = [];
        $categories = $this->materialsCategoriesRepository->findBy([
            'is_deleted' => false
        ]);

        if (!$categories) {
            return $result;
        }

        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
                'parent_id' => $category->getParent() ? $category->getParent()->getId() : null,
                'is_required' => $category->getIsRequired(),
                'is_favorite' => !empty($this->materialsCategoriesFavoritesRepository->findOneBy([
                    'user' => $user,
                    'category' => $category->getId()
                ])),
                'code' => $category->getCode()
            ];
        }

        $data = $this->createTree($result);

        return $data;
    }

    /**
     * @param mixed $arr
     *
     * @return [type]
     */
    private function createTree($arr)
    {
        $parents_arr = array();
        foreach ($arr as $key => $item) {
            if ($item['parent_id'] == null) {
                $item['parent_id'] = 0;
            }
            $parents_arr[$item['parent_id']][$item['id']] = $item;
        }
        $treeElem = $parents_arr[0];
        $this->generateElemTree($treeElem, $parents_arr);

        return $treeElem;
    }

    /**
     * @param mixed $treeElem
     * @param mixed $parents_arr
     *
     * @return [type]
     */
    private function generateElemTree(&$treeElem, $parents_arr)
    {
        foreach ($treeElem as $key => $item) {
            if (!isset($item['child'])) {
                $treeElem[$key]['child'] = array();
            }
            if (array_key_exists($key, $parents_arr)) {
                $treeElem[$key]['child'] = $parents_arr[$key];
                $this->generateElemTree($treeElem[$key]['child'], $parents_arr);
            }
        }
    }

    /**
     * Список всех категорий для пользователя
     *
     * @return [type]
     */
    public function getUserAllCategories($user)
    {
        $result = [];
        $categories = $this->materialsCategoriesRepository->findBy([
            'is_deleted' => false
        ]);

        if (!$categories) {
            return $result;
        }

        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
                'parent_id' => $category->getParent() ? $category->getParent()->getId() : null,
                'is_required' => $category->getIsRequired(),
                'is_favorite' => !empty($this->materialsCategoriesFavoritesRepository->findOneBy([
                    'user' => $user,
                    'category' => $category->getId()
                ])),
                'code' => $category->getCode()
            ];
        }

        return $result;
    }

    /**
     * @param int $category_id
     *
     * @return [type]
     */
    public function getCategoryById(int $category_id)
    {
        $result = [];
        $category = $this->materialsCategoriesRepository->findOneBy([
            'is_deleted' => false,
            'required' => false,
            'id' => $category_id
        ]);

        if (!$category) {
            return $result;
        }

        $result = $this->getCategoryInfo($category);

        return $result;
    }

    /**
     * @param MaterialsCategories $category
     *
     * @return [type]
     */
    private function getCategoryInfo(MaterialsCategories $category)
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'parent_id' => $category->getParent() ? $category->getParent()->getId() : null,
            'parent_name' => $category->getParent() ? $category->getParent()->getName() : null,
            'all_parents' => $category->getParent() ? $this->getCategoriesStringList($category) : null
        ];
    }

    /**
     * Получить список категорий массивом
     *
     * @param MaterialsCategories $category
     *
     * @return [type]
     */
    private function getCategoriesStringList(MaterialsCategories $category)
    {
        $list = [];
        while ($category->getParent()) {
            array_unshift($list, $category->getParent()->getName());
            if ($category->getParent()) {
                $category = $category->getParent();
            }
        }

        return $list;
    }
}
