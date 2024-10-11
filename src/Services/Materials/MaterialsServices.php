<?php

namespace App\Services\Materials;

// Entity
use App\Entity\Materials;
use App\Entity\MaterialsStreamAccess;
use App\Entity\User;
// Repository
use App\Repository\MaterialsRepository;
use App\Repository\MaterialsFavoritesRepository;
use App\Repository\MaterialsCategoriesFavoritesRepository;
use App\Repository\LikesRepository;
use App\Repository\FilesRepository;
use App\Repository\MaterialsStreamAccessRepository;
use App\Repository\FormattedVideoRepository;
// Services
use App\Services\File\FileServices;
use App\Services\Providers\FacecastServices;
// Etc
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Exception\LogicException;
use Doctrine\ORM\EntityManagerInterface;

class MaterialsServices
{
    private EntityManagerInterface $em;
    private MaterialsRepository $materialsRepository;
    private MaterialsCategoriesFavoritesRepository $materialsCategoriesFavoritesRepository;
    private MaterialsFavoritesRepository $materialsFavoritesRepository;
    private LikesRepository $likesRepository;
    private FilesRepository $filesRepository;
    private FormattedVideoRepository $formattedVideoRepository;
    private MaterialsStreamAccessRepository $materialsStreamAccessRepository;
    private FacecastServices $facecastServices;
    private ParameterBagInterface $params;

    public const ACCESS_SUBSCRIPTION = 0;
    public const ACCESS_VIP = 1;
    public const ACCESS_EVERYBODY = 2;

    protected const SORT_ASC = 'asc';
    protected const SORT_DESC = 'desc';

    // Статус эфира. Скоро начнется, в процессе, закончился.
    protected const STREAM_COMING = 'coming';
    protected const STREAM_RUNNING = 'running';
    protected const STREAM_FINISHED = 'finished';

    public const ACCESS = [
        self::ACCESS_SUBSCRIPTION, self::ACCESS_VIP, self::ACCESS_EVERYBODY
    ];

    public const TYPE_AUDIO = 'audio';
    public const TYPE_VIDEO = 'video';
    public const TYPE_STREAM = 'stream';
    public const TYPE_ARTICLE = 'article';
    public const TYPE_MEDITATION = 'meditation';

    public const TYPES = [
        self::TYPE_AUDIO,
        self::TYPE_VIDEO,
        self::TYPE_STREAM,
        self::TYPE_ARTICLE,
        self::TYPE_MEDITATION
    ];

    public const MAIN_PAGE_ITEMS = 5;

    public const PAGE_OFFSET = 10;

    public function __construct(
        EntityManagerInterface $em,
        MaterialsRepository $materialsRepository,
        MaterialsFavoritesRepository $materialsFavoritesRepository,
        MaterialsCategoriesFavoritesRepository $materialsCategoriesFavoritesRepository,
        LikesRepository $likesRepository,
        FilesRepository $filesRepository,
        MaterialsStreamAccessRepository $materialsStreamAccessRepository,
        FacecastServices $facecastServices,
        ParameterBagInterface $params,
        FormattedVideoRepository $formattedVideoRepository
    ) {
        $this->em = $em;
        $this->materialsRepository = $materialsRepository;
        $this->materialsFavoritesRepository = $materialsFavoritesRepository;
        $this->materialsCategoriesFavoritesRepository = $materialsCategoriesFavoritesRepository;
        $this->likesRepository = $likesRepository;
        $this->filesRepository = $filesRepository;
        $this->materialsStreamAccessRepository = $materialsStreamAccessRepository;
        $this->facecastServices = $facecastServices;
        $this->params = $params;
        $this->formattedVideoRepository = $formattedVideoRepository;
    }

    /**
     * Получить список материалов для пользователей
     *
     * @param User $user
     * @param array $filter
     * @param bool $count
     * @param bool $is_favorite
     *
     * @return [type]
     */
    public function getMaterials(
        User $user,
        array $filter,
        bool $count = false,
        bool $is_favorite = false,
        bool $is_admin = false
    ) {
        $filter['offset'] = (intval($filter['page'] - 1)) * self::PAGE_OFFSET;
        $filter['limit'] = self::PAGE_OFFSET;

        $materials = $this->materialsRepository->getMaterials(
            $user,
            $filter,
            [],
            $count,
            $is_favorite,
            $is_admin
        );

        if ($count) {
            return $materials;
        }

        $result = [];
        if (!$materials) {
            return $result;
        }

        foreach ($materials as $material) {
            $extra_fields = $this->getExtraFields($material);

            $result[] = $this->getMaterialInfo($material[0], $user, $extra_fields);
        }

        return $result;
    }

    public function getShowBillMaterials(
        User $user,
        array $order_by
    ) {
        $result = [];

        $materials = $this->materialsRepository->getShowBillMaterials(
            $user,
            $order_by
        );

        if (!$materials) {
            return $result;
        }

        foreach ($materials as $material) {
            $result[] = $this->getShowBillData($material);
        }

        return $result;
    }

    /**
     * Поиск материала по ID
     *
     * @param mixed $material_id
     *
     * @return [type]
     */
    public function getMaterialById($material_id, $user)
    {
        $result = [];
        $material = $this->materialsRepository->getMaterialById($user, $material_id);

        if (empty($material)) {
            return $result;
        }

        $material = reset($material);

        $extra_fields = $this->getExtraFields($material);

        return $this->getMaterialInfo($material[0], $user, $extra_fields);
    }

    /**
     * Добавить просмотр материалу
     *
     * @param mixed $material_id
     *
     * @return [type]
     */
    public function markViewed($material_id)
    {
        $material = $this->materialsRepository->find($material_id);
        if (!$material) {
            return false;
        }

        $material->setViewsCount($material->getViewsCount() + 1);
        $this->em->persist($material);
        $this->em->flush();
        return true;
    }

    /**
     * Список стримов для главной страницы
     *
     * @param User $user
     *
     * @return [type]
     */
    public function getMainPageStreams(User $user)
    {
        $result = [];
        $streams = $this->materialsRepository->getStreamsMainPage($user);

        if ($streams) {
            foreach ($streams as $stream) {
                $extra_fields = $this->getExtraFields($stream);

                $result[] = $this->getMaterialInfo($stream[0], $user, $extra_fields);
            }
        }

        return $result;
    }

    /**
     * Список материалов для главной страницы
     *
     * @return [type]
     */
    public function getMainPageMaterials($user)
    {
        $result = [];

        $favorite_ids = $this->materialsCategoriesFavoritesRepository->lastFavoriteIds($user);
        $filter = [
            'limit' => self::MAIN_PAGE_ITEMS,
            'offset' => 0,
            'category_slug' => null,
            'sort_param' => null,
            'sort_type' => null,
        ];

        // Если есть любимые категории, поиск по этим категориям
        if ($favorite_ids) {
            $materials = $this->materialsRepository->getMaterials(
                $user,
                $filter,
                $favorite_ids,
                false,
                false,
                false,
                true
            );
            if (count($materials) < self::MAIN_PAGE_ITEMS) {
                $limit = self::MAIN_PAGE_ITEMS - count($materials);
                $additional_materials = $this->materialsRepository->getMaterialsWithoutIds(
                    $user,
                    $limit,
                    $favorite_ids,
                );
                if ($additional_materials) {
                    $materials = array_merge($materials, $additional_materials);
                }
            }
        } else {
            $materials = $this->materialsRepository->getMaterials(
                $user,
                $filter,
                [],
                false,
                false,
                false,
                true
            );
        }

        if (!$materials) {
            return $result;
        }

        foreach ($materials as $material) {
            $extra_fields = $this->getExtraFields($material);

            $result[] = $this->getMaterialInfo($material[0], $user, $extra_fields);
        }

        return $result;
    }

    /**
     * Фильтр материалов (поиск)
     *
     * @param User $user
     * @param array $filter
     *
     * @return [type]
     */
    public function filterMaterials(User $user, string $search)
    {
        $result = [];
        $materials = $this->materialsRepository->filterMaterials($user, $search);
        if (!$materials) {
            return [];
        }

        foreach ($materials as $material) {
            $extra_fields = $this->getExtraFields($material);

            $result[] = $this->getMaterialInfo($material[0], $user, $extra_fields);
        }

        return $result;
    }

    /**
     * Получить информацию о материале
     *
     * @param mixed $material
     * @param mixed $user
     *
     * @return [type]
     */
    public function getMaterialInfo($material, $user, array $extra_fields = null)
    {
        $lazy_post = $material->getLazyPost();
        $stream_start = $material->getStreamStart();
        $create_time = $material->getCreateTime();

        if (!empty($lazy_post)) {
            $create_time = $lazy_post;
        } elseif (!empty($stream_start)) {
            $create_time = $stream_start;
        }


        $result = [
            'id' => $material->getId(),
            'title' => $material->getTitle(),
            'description' => $material->getDescription(),
            'short_description' => $material->getShortDescription(),
            'views_count' => $material->getViewsCount(),
            'is_favorite' => !empty($this->materialsFavoritesRepository->count([
                'user' => $user->getId(),
                'material' => $material->getId()
            ])),
            'likes_collector_id' => $material->getLikesCollector()->getId(),
            'comments_collector_id' => $material->getCommentsCollector()->getId(),
            'is_show_bill' => $material->getIsShowBill(),
            'create_time' => $create_time,
            'create_time_formatted' => date('Y-m-d', $create_time),
            'update_time' => $material->getUpdateTime(),
            'category_id' => $material->getCategory()->getId(),
            'category_name' => $material->getCategory()->getName(),
            'category_slug' => $material->getCategory()->getSlug(),
            'stream_start' => $material->getStreamStart(),
            'stream_url' => $material->getStream(),
            'stream_key' => null,
            'stream_status' => null,
            'is_stream_finished' => $material->getIsStreamFinished(),
            'lazy_post' =>  $material->getLazyPost(),
            'is_posted' => true,
            'access' => $material->getAccess(),
            'type' => $material->getType(),
            'category_parent_id' => null,
            'audio' => $material->getAudio() ? $material->getAudio()->getFileAsArray() : null,
            'video' => $material->getVideo()  ? $material->getVideo()->getFileAsArray() : null,
            'video_formatter' => [],
            'article_files' => [],
            'is_liked' => !empty($this->likesRepository->findOneBy([
                'user' => $user,
                'likes_collector' => $material->getLikesCollector(),
                'is_like' => true
            ])),
            'preview_image' => $material->getPreviewImage()  ? $material->getPreviewImage()->getFileAsArray() : null,
        ];

        if ($material->getType() == self::TYPE_STREAM) {
            // Статус эфира.
            if ($material->getStreamStart()) {
                // Эфир скоро начнется
                if ($material->getStreamStart() > time()) {
                    $result['stream_status'] = self::STREAM_COMING;
                }
                // Эфир закончился
                if ($material->getIsStreamFinished()) {
                    $result['stream_status'] = self::STREAM_FINISHED;
                }
                // Эфир идет
                if ($material->getStreamStart() <= time() && !$material->getIsStreamFinished()) {
                    $result['stream_status'] = self::STREAM_RUNNING;
                }
            }


            $stream_key = $this->materialsStreamAccessRepository->findOneBy([
                'user' => $user,
                'material' => $material
            ]);

            if ($stream_key) {
                $result['stream_key'] = $stream_key->getUserKey();
            }

            $result['video_formatter'] = $this->getFormattedVideo($material);
        }

        if ($material->getLazyPost() && $material->getLazyPost() > time()) {
            $result['is_posted'] = false;
        }

        if ($extra_fields) {
            $result = array_merge($result, $extra_fields);
        }

        if ($material->getType() == MaterialsServices::TYPE_ARTICLE) {
            $exist_files = $this->filesRepository->findBy([
                'materials' => $material->getId(),
                'file_type' => FileServices::TYPE_MATERIAL_ARTICLE
            ]);
            if ($exist_files) {
                foreach ($exist_files as $files) {
                    $result['article_files'][] = $files->getFileAsArray();
                }
            }
        }

        if ($material->getCategory()->getParent()) {
            $result['category_parent_id'] = $material->getCategory()->getParent()->getId();
        }

        return $result;
    }

    /**
     * Данные для отображения в афише
     *
     * @param Materials $material
     *
     * @return [type]
     */
    public function getShowBillData(Materials $material)
    {
        return [
            'id' => $material->getId(),
            'title' => $material->getTitle(),
            'category_id' => $material->getCategory()->getId(),
            'category_name' => $material->getCategory()->getName(),
            'category_slug' => $material->getCategory()->getSlug(),
            'preview_image' => $material->getPreviewImage()  ? $material->getPreviewImage()->getFileAsArray() : null,
            'lazy_post' =>  $material->getLazyPost(),
            'short_description' => $material->getShortDescription(),
            'type' => $material->getType(),
            'create_time_formatted' => date('Y-m-d', $material->getCreateTime()),
            'lazy_post_formatted' => date('Y-m-d H:i', $material->getLazyPost()),
        ];
    }

    /**
     * Создание ключа для стрима или получение существующего
     *
     * @param User $user
     * @param int $material_id
     *
     * @return [type]
     */
    public function getUserStreamKey(User $user, int $material_id)
    {
        $material = $this->materialsRepository->find($material_id);
        if (!$material) {
            throw new LogicException('Материал не найден');
        }

        if ($material->getType() != MaterialsServices::TYPE_STREAM) {
            throw new LogicException('Тип материала должен быть эфиром');
        }

        if (!$material->getStream()) {
            throw new LogicException('Нужно указать ссылку на эфир для материала');
        }

        $code = md5(
            md5($user->getId() .
                $this->params->get('facecast.key_salt')) .
                $this->params->get('facecast.key_salt')
        );

        $access = $this->materialsStreamAccessRepository->findOneBy([
            'material' => $material->getId(),
            'user' => $user->getId()
        ]);

        if ($access) {
            if ($material->getStream() != $access->getStreamUrl()) {
                if (!$this->facecastServices->addUserForStream($user, $material, $code)) {
                    throw new LogicException('Ошибка. Не удалось добавить пользователя для эфира');
                }
                $access->setStreamUrl($material->getStream());
                $this->em->persist($access);
                $this->em->flush();
            }
            return $access->getUserKey();
        }

        if (!$this->facecastServices->addUserForStream($user, $material, $code)) {
            throw new LogicException('Ошибка. Не удалось добавить пользователя для эфира');
        }

        $access = new MaterialsStreamAccess();
        $access
            ->setUser($user)
            ->setMaterial($material)
            ->setUserKey($code)
            ->setStreamUrl($material->getStream());
        $this->em->persist($access);
        $this->em->flush();

        return $access->getUserKey();
    }

    /**
     * Закрытие активных эфиров
     *
     * @return [type]
     */
    public function updateStreamsStatus(): bool
    {
        $status = false;
        $streams = $this->materialsRepository->getActiveStreams();
        if (!$streams) {
            return $status;
        }

        foreach ($streams as $stream) {
            $event_id = $this->facecastServices->getEventIdByUrl($stream->getStream());
            if (!$event_id) {
                $stream->setIsStreamFinished(true);
                $this->em->persist($stream);
                $this->em->flush();
                $status = true;
            }
        }

        return $status;
    }

    /**
     * @param Materials $material
     *
     * @return [type]
     */
    private function getFormattedVideo(Materials $material)
    {
        $result = [];
        if (!$material->getVideo()) {
            return $result;
        }

        $videos_formatted = $this->formattedVideoRepository->findBy([
            'file' => $material->getVideo()->getId(),
            'convertation_status' => 2
        ]);

        if (!$videos_formatted) {
            return $result;
        }

        foreach ($videos_formatted as $video_formatted) {
            $result[$video_formatted->getType()] = $material->getVideo()->getFileAsArray();
        }

        return $result;
    }

    /**
     * Доп поля для материала
     *
     * @param array $material
     *
     * @return [type]
     */
    public function getExtraFields(array $material)
    {
        return [
            'comments_count' => (int)$material['comments_count'],
            'likes_count' => (int)$material['likes_count']
        ];
    }

    /**
     * Формирование данных для фильтра материалов
     *
     * @param Request $request
     * @param null $category
     *
     * @return [type]
     */
    public function getMaterialsFilterData(Request $request, $category = null)
    {
        // Сортировка
        $filter['sort_param'] = mb_strtolower($request->query->get('sort'));
        $order = mb_strtolower($request->query->get('order'));
        $filter['sort_type'] = ($order == self::SORT_ASC) ? self::SORT_ASC : self::SORT_DESC;

        // Номер страницы
        $page = (int)$request->query->get('page');
        $filter['page'] = empty($page) ? 1 : $page;

        // Категория
        $filter['category_slug'] = $category ?  trim($category) : null;

        // Тип материала
        $type = mb_strtolower($request->query->get('type'));
        if (!in_array($type, self::TYPES)) {
            $type = "";
        }
        $filter['type'] = $type;

        // Поиск
        $filter['search'] = trim($request->query->get('search'));

        return $filter;
    }
}
