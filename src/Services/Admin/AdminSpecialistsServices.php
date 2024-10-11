<?php

namespace App\Services\Admin;

//Entity
use App\Entity\Specialists;
// Repository
use App\Repository\UserRepository;
use App\Repository\MaterialsRepository;
use App\Repository\CommentsRepository;
use App\Repository\LikesRepository;
use App\Repository\SpecialistsRepository;
use App\Repository\FilesRepository;
use App\Repository\ConsultationsRepository;
use App\Repository\SpecialistsRequestsRepository;
use App\Repository\SpecialistsCategoriesRepository;
// Services
use App\Services\Materials\MaterialsServices;
use App\Services\File\FileServices;
//Symfony
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

class AdminSpecialistsServices
{
    public const PAGE_OFFSET = 10;

    private EntityManagerInterface $em;
    private FilesRepository $filesRepository;
    private SpecialistsRepository $specialistsRepository;
    private SpecialistsRequestsRepository $specialistsRequestsRepository;
    private SpecialistsCategoriesRepository $specialistsCategoriesRepository;
    private ConsultationsRepository $consultationsRepository;

    public function __construct(
        EntityManagerInterface $em,
        FilesRepository $filesRepository,
        SpecialistsRepository $specialistsRepository,
        SpecialistsRequestsRepository $specialistsRequestsRepository,
        SpecialistsCategoriesRepository $specialistsCategoriesRepository,
        ConsultationsRepository $consultationsRepository
    ) {
        $this->em = $em;
        $this->filesRepository = $filesRepository;
        $this->specialistsRepository = $specialistsRepository;
        $this->specialistsRequestsRepository = $specialistsRequestsRepository;
        $this->specialistsCategoriesRepository = $specialistsCategoriesRepository;
        $this->consultationsRepository = $consultationsRepository;
    }

    /**
     * Создать специалиста
     *
     * @param mixed $form
     *
     * @return Specialists
     */
    public function createSpecialist($form): Specialists
    {
        $fio = $form->get('fio')->getData();
        $experience = $form->get('experience')->getData();
        $speciality = $form->get('speciality')->getData();
        $price = $form->get('price')->getData();
        $sort = $form->get('sort')->getData();
        $avatar = $form->get('avatar')->getData();
        $email = $form->get('email')->getData();

        $specialist = new Specialists();
        $specialist->setFio($fio)
            ->setExperience($experience)
            ->setSpeciality($speciality)
            ->setPrice($price)
            ->setSort($sort)
            ->setEmail($email)
            ->setIsActive(true);

        $avatar->setSpecialist($specialist)->setIsActive(true);
        $this->em->persist($avatar);
        $this->em->persist($specialist);
        $this->em->flush();

        return $specialist;
    }

    /**
     * Редактировать специалиста
     *
     * @param mixed $form
     *
     * @return Specialists
     */
    public function editSpecialist($form): Specialists
    {
        $fio = $form->get('fio')->getData();
        $experience = $form->get('experience')->getData();
        $speciality = $form->get('speciality')->getData();
        $price = $form->get('price')->getData();
        $sort = $form->get('sort')->getData();
        $is_active = $form->get('is_active')->getData() ?? 0;
        $avatar = $form->get('avatar')->getData();
        $specialist = $form->get('specialist')->getData();
        $email = $form->get('email')->getData();

        $old_avatar = $this->filesRepository->getSpecialistAvatar($specialist);
        if ($old_avatar) {
            $old_avatar->setSpecialist(null)->setIsActive(false);
            $this->em->persist($old_avatar);
            $this->em->flush();
        }

        $specialist->setFio($fio)
            ->setExperience($experience)
            ->setSpeciality($speciality)
            ->setPrice($price)
            ->setSort($sort)
            ->setIsActive($is_active)
            ->setEmail($email);

        $avatar->setSpecialist($specialist)->setIsActive(true);
        $this->em->persist($avatar);
        $this->em->persist($specialist);
        $this->em->flush();

        return $specialist;
    }

    /**
     * @param mixed $specialist_id
     *
     * @return Specialists
     */
    public function deleteSpecialist($specialist_id): Specialists
    {
        if (empty($specialist_id)) {
            throw new LogicException("Нужно указать ID специалиста");
        }
        $specialist = $this->specialistsRepository->find($specialist_id);

        if (!$specialist) {
            throw new LogicException("Специалист не найден");
        }

        if ($specialist->getIsDeleted()) {
            throw new LogicException("Специалист уже удален");
        }

        $specialist->setIsDeleted(true);
        $this->em->persist($specialist);
        $this->em->flush();

        return $specialist;
    }

    /**
     * Список специалистов для админ панели
     *
     * @param mixed $page
     * @param mixed $order
     *
     * @return [type]
     */
    public function getSpecialistsList($page, $order_by, $search)
    {
        $result = [];
        $specialists = $this->specialistsRepository->getAdminSpecialists($page, $order_by, $search);

        if (!$specialists) {
            return [
                'specialists' => $result,
                'specialists_count' => 0
            ];
        }

        $specialists_count = (int)$this->specialistsRepository->getAdminSpecialists($page, $order_by, $search, true);
        foreach ($specialists as $specialist) {
            $result[] = $this->getSpecialistsData($specialist);
        }

        return [
            'specialists' => $result,
            'specialists_count' => $specialists_count
        ];
    }

    /**
     * Список специалистов для panel
     *
     * @param int $category_id
     * @param $order_by
     * @param int|null $limit
     * @return array [type]
     */
    public function getSpecialists($category_id, $order_by, ?int $limit = null)
    {
        if (empty($order_by['sort_param']) || !in_array($order_by['sort_param'], ['price', 'sort'])) {
            $order_by['sort_param'] = 'sort';
        }

        $result = [];
        $specialists = [];
        if (empty(intval($category_id))) {
            $specialists = $this->specialistsRepository->findBy([
                'is_deleted' => false,
                'is_active' => 1
            ], [$order_by['sort_param'] => $order_by['sort_type']], $limit);
        } else {
            $consultation = $this->consultationsRepository->find($category_id);
            if (!$consultation) {
                return $result;
            }

            $specialist_category = $this->specialistsCategoriesRepository->getSpecialistsByCategory(
                $consultation,
                $order_by,
                $limit
            );

            foreach ($specialist_category as $specialist) {
                $specialists[] = $specialist->getSpecialist();
            }
        }

        foreach ($specialists as $specialist) {
            $result[] = $this->getSpecialistsData($specialist);
        }

        return $result;
    }

    /**
     * Поиск спеца по Id
     *
     * @param mixed $id
     *
     * @return [type]
     */
    public function getSpecialistById($id)
    {
        $specialist = $this->specialistsRepository->findOneBy([
            'id' => $id,
            'is_deleted' => 0
        ]);

        if (!$specialist) {
            return [];
        }

        return $this->getSpecialistsData($specialist);
    }

    /**
     * @param Specialists $specialist
     *
     * @return [type]
     */
    public function getSpecialistsData(Specialists $specialist)
    {
        $avatar = $this->filesRepository->getSpecialistAvatar($specialist);
        $result = [
            'id' => $specialist->getId(),
            'fio' => $specialist->getFio(),
            'experience' => $specialist->getExperience(),
            'price' => $specialist->getPrice(),
            'speciality' => $specialist->getSpeciality(),
            'email' => $specialist->getEmail(),
            'is_active' => $specialist->getIsActive(),
            'sort' => $specialist->getSort(),
            // 'avatar' => $avatar ? $avatar->getFilePath() : null,
            'categories' => []
        ];

        if ($avatar) {
            $result['avatar'] = $avatar->getFileAsArray();
        }


        $categories = $specialist->getCategories();
        if ($categories) {
            foreach ($categories as $category) {
                if (!$category->getConsultation()->getIsDeleted()) {
                    $result['categories'][] = [
                        'id' => $category->getConsultation()->getId(),
                        'name' => $category->getConsultation()->getName()
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Список заявок для консультации
     *
     * @param mixed $page
     *
     * @return [type]
     */
    public function getSpecialistsRequests($page)
    {
        $result = [
            'requests' => [],
            'requests_count' => 0,
            'pages_count' => 0
        ];

        $offset = ($page - 1) * self::PAGE_OFFSET;
        $limit = self::PAGE_OFFSET;

        $requests = $this->specialistsRequestsRepository->getRequests($limit, $offset);
        if (!$requests) {
            return $result;
        }

        foreach ($requests as $request) {
            $result['requests'][] = [
                'id' => $request->getId(),
                'create_time' => date('d-m-Y', $request->getCreateTime()),
                'fio' => $request->getFio(),
                'phone' => $request->getPhone(),
                'email' => $request->getEmail(),
                'comment' => $request->getComment(),
                'specialist_fio' => $request->getSpecialist()->getFio(),
                'specialist_id' => $request->getSpecialist()->getId(),
            ];
        }

        $result['requests_count'] = (int)$this->specialistsRequestsRepository->getRequests($limit, $offset, true);
        $result['pages_count'] = ceil($result['requests_count'] / self::PAGE_OFFSET);

        return $result;
    }

    /**
     * Список всех активных спецов
     *
     * @return [type]
     */
    public function getSpecialistsAll()
    {
        $specialists = $this->specialistsRepository->getAllSpecialistsArray();
        if (!$specialists) {
            return [];
        }

        return $specialists;
    }
}
