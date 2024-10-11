<?php

namespace App\Services\Admin;

use App\Entity\Consultations;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\SpecialistsCategories;
use App\Repository\SpecialistsRepository;
use App\Repository\SpecialistsCategoriesRepository;
use App\Repository\ConsultationsRepository;
use Symfony\Component\Security\Core\Exception\LogicException;

class AdminSpecialistsCategoriesServices
{
    private EntityManagerInterface $em;
    private SpecialistsRepository $specialistsRepository;
    private SpecialistsCategoriesRepository $specialistsCategoriesRepository;
    private ConsultationsRepository $consultationsRepository;

    public function __construct(
        EntityManagerInterface $em,
        SpecialistsRepository $specialistsRepository,
        SpecialistsCategoriesRepository $specialistsCategoriesRepository,
        ConsultationsRepository $consultationsRepository
    ) {
        $this->em = $em;
        $this->specialistsRepository = $specialistsRepository;
        $this->specialistsCategoriesRepository = $specialistsCategoriesRepository;
        $this->consultationsRepository = $consultationsRepository;
    }

    /**
     * Создать категорию для специалистов
     *
     * @param mixed $form
     *
     * @return Specialists
     */
    public function createSpecialistCategory($form): bool
    {
        $name = $form->get('name')->getData();
        $specialists_ids = $form->get('specialists_ids')->getData();

        $consultation = new Consultations();
        $consultation->setName($name);
        $this->em->persist($consultation);
        $this->em->flush();

        foreach ($specialists_ids as $specialist_id) {
            $category = new SpecialistsCategories();
            $category
                ->setConsultation($consultation)
                ->setSpecialist($this->specialistsRepository->find($specialist_id));

            $this->em->persist($category);
            $this->em->flush();
        }

        return true;
    }

    /**
     * Создать категорию для специалистов
     *
     * @param mixed $form
     *
     * @return Specialists
     */
    public function editSpecialistCategory($form): bool
    {
        $name = $form->get('name')->getData();
        $specialists_ids = $form->get('specialists_ids')->getData();
        $consultation = $form->get('consultation_id')->getData();

        $consultation->setName($name);
        $this->em->persist($consultation);
        $this->em->flush();

        $exist_data = $this->specialistsCategoriesRepository->getSpecialistsByCategory($consultation);
        // Удаляем не актуальных специалистов
        foreach ($exist_data as $specialist_category) {
            $a = $specialist_category->getSpecialist()->getId();
            if (!in_array($specialist_category->getSpecialist()->getId(), $specialists_ids)) {
                $this->em->remove($specialist_category);
                $this->em->flush();
            } else {
                $key = array_search($specialist_category->getSpecialist()->getId(), $specialists_ids);
                if (false !== $key) {
                    unset($specialists_ids[$key]);
                }
            }
        }

        foreach ($specialists_ids as $specialist_id) {
            $category = new SpecialistsCategories();
            $category
                ->setConsultation($consultation)
                ->setSpecialist($this->specialistsRepository->find($specialist_id));
            $this->em->persist($category);
            $this->em->flush();
        }

        return true;
    }

    /**
     * Удаление категорий специалиста
     *
     * @param int $id
     *
     * @return [type]
     */
    public function deleteSpecialistCategory(int $id)
    {
        $category = $this->consultationsRepository->find($id);
        if (!$category) {
            throw new LogicException("Категория не найдена");
        }

        if ($category->getIsDeleted()) {
            throw new LogicException("Категория уже была удалена");
        }

        $category->setIsDeleted(true);
        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    /**
     * Список категорий
     *
     * @return [type]
     */
    public function getCategories()
    {
        return $this->consultationsRepository->getCategories();
    }

    /**
     * Список категорий для админ панели
     *
     * @return [type]
     */
    public function getCategoriesAdmin(string $search = "")
    {
        $result = [];
        $categories = $this->consultationsRepository->getCategoriesAdmin($search);
        if (!$categories) {
            return [];
        }

        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'specialists' => $this->getCategorySpecialists($category)
            ];
        }

        return $result;
    }

    /**
     * Список категорий для админ панели
     *
     * @return [type]
     */
    public function getAdminCategoryById($id)
    {
        $result = [];
        $category = $this->consultationsRepository->findOneBy([
            'id' => $id,
            'is_deleted' => false
        ]);
        if (!$category) {
            return [];
        }

        $result[] = [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'specialists' => $this->getCategorySpecialists($category)
        ];

        return $result;
    }

    /**
     * @param mixed $category
     *
     * @return [type]
     */
    private function getCategorySpecialists($category)
    {
        $result = [];
        $specialists = $category->getSpecialists();
        foreach ($specialists as $specialist_category) {
            if (
                !$specialist_category->getSpecialist()->getIsDeleted() &&
                $specialist_category->getSpecialist()->getIsActive()
            ) {
                $result[] = [
                    'id' => $specialist_category->getSpecialist()->getId(),
                    'fio' => $specialist_category->getSpecialist()->getFio(),
                    'email' => $specialist_category->getSpecialist()->getEmail(),
                    'speciality' => $specialist_category->getSpecialist()->getSpeciality(),
                ];
            }
        }

        return $result;
    }
}
