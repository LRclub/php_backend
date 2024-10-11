<?php

namespace App\Services\Admin;

// Entity
use App\Entity\Materials;
use App\Entity\CommentsCollector;
use App\Entity\LikesCollector;
// Repository
use App\Repository\MaterialsRepository;
use App\Repository\MaterialsCategoriesRepository;
use App\Repository\FilesRepository;
// Services
use App\Services\Materials\MaterialsServices;
use App\Services\File\FileServices;
use App\Services\Providers\FacecastServices;
// Etc
use Symfony\Component\Security\Core\Exception\LogicException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\MaterialsNotificationEvent;

class AdminMaterialsServices
{
    private EntityManagerInterface $em;
    private MaterialsRepository $materialsRepository;
    private MaterialsServices $materialsServices;
    private FilesRepository $filesRepository;
    private FileServices $fileServices;
    private MaterialsCategoriesRepository $materialsCategoriesRepository;
    private FacecastServices $facecastServices;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface $em,
        MaterialsRepository $materialsRepository,
        MaterialsServices $materialsServices,
        FilesRepository $filesRepository,
        FileServices $fileServices,
        MaterialsCategoriesRepository $materialsCategoriesRepository,
        FacecastServices $facecastServices,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->materialsRepository = $materialsRepository;
        $this->materialsServices = $materialsServices;
        $this->filesRepository = $filesRepository;
        $this->fileServices = $fileServices;
        $this->materialsCategoriesRepository = $materialsCategoriesRepository;
        $this->facecastServices = $facecastServices;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Создать материал
     *
     * @return [type]
     */
    public function addMaterial($form, $user): Materials
    {
        $data = $this->getMaterialsData($form);

        // Comments сollector
        $comments_collector = new CommentsCollector();
        $this->em->persist($comments_collector);
        $this->em->flush();

        // Likes сollector
        $likes_collector = new LikesCollector();
        $this->em->persist($likes_collector);
        $this->em->flush();

        // Material
        $material = new Materials();
        $audio = $data['audio'];
        $video = $data['video'];
        $category = $data['category'];

        switch ($data['type']) {
            case $this->materialsServices::TYPE_AUDIO:
                if ($audio) {
                    $material->setAudio($audio);
                }
                break;
            case $this->materialsServices::TYPE_VIDEO:
                if ($video) {
                    $material->setVideo($video);
                }
                break;
            case $this->materialsServices::TYPE_STREAM:
                if ($video) {
                    $material->setVideo($video);
                }
                $event_id = $this->facecastServices->getEventIdByUrl($data['stream_url']);
                $material
                    ->setStream($data['stream_url'])
                    ->setStreamStart($data['stream_start']->getTimestamp())
                    ->setStreamEventId($event_id);
                break;
            case $this->materialsServices::TYPE_MEDITATION:
                $category = $this->materialsCategoriesRepository->findOneBy(['code' => 'meditation']);
                if ($audio) {
                    $material->setAudio($audio);
                }
                if (!$category) {
                    throw new LogicException("Ошибка. Категория медитаций отсутствует");
                }
                break;
        }

        $material
            ->setCommentsCollector($comments_collector)
            ->setLikesCollector($likes_collector)
            ->setTitle($data['title'])
            ->setDescription($data['description'])
            ->setShortDescription($data['short_description'])
            ->setPreviewImage($data['preview_image'])
            ->setAccess($data['access'])
            ->setLazyPost(empty($data['lazy_post']) ? null : $data['lazy_post']->getTimestamp())
            ->setIsShowBill($data['is_show_bill'])
            ->setCategory($category)
            ->setType($data['type'])
            ->setCreateTime(time());

        $this->em->persist($material);
        $this->em->flush();

        // Привязываем файл к материалу
        switch ($data['type']) {
            case $this->materialsServices::TYPE_AUDIO:
            case $this->materialsServices::TYPE_MEDITATION:
                if ($audio) {
                    $audio->setMaterials($material);
                    $this->em->persist($audio);
                    $this->em->flush();
                }
                break;
            case $this->materialsServices::TYPE_VIDEO:
                if ($video) {
                    $video->setMaterials($material);
                    $this->em->persist($video);
                    $this->em->flush();
                }
                break;
            case $this->materialsServices::TYPE_ARTICLE:
                if ($data['article_files']) {
                    foreach ($data['article_files'] as $file) {
                        $find_file = $this->filesRepository->find(intval($file));
                        if (
                            !$this->fileServices->isMaterialsArticleExist(
                                $user,
                                $find_file,
                                $material
                            )
                        ) {
                            $find_file->setMaterials($material);
                            $this->em->persist($find_file);
                            $this->em->flush();
                        }
                    }
                }
                break;
        }

        // Выставляем для фото материал
        $data['preview_image']->setMaterials($material);
        $this->em->persist($data['preview_image']);
        $this->em->flush();

        // Задаем материал для LikesCollector
        $likes_collector->setMaterial($material);
        $this->em->persist($likes_collector);
        $this->em->flush();

        if (empty($material->getLazyPost())) {
            $this->eventDispatcher->dispatch(
                new MaterialsNotificationEvent($material),
                MaterialsNotificationEvent::NOTIFICATION_MATERIAL_NEW
            );
        }

        return $material;
    }

    /**
     * Редактировать материал
     *
     * @param mixed $form
     *
     * @return [type]
     */
    public function editMaterial($form, $user): Materials
    {
        $data = $this->getMaterialsData($form);
        $material = $data['material'];
        $audio = $data['audio'];
        $video = $data['video'];
        $category = $data['category'];

        switch ($data['type']) {
            case $this->materialsServices::TYPE_AUDIO:
                if ($audio) {
                    $material->setAudio($audio);
                }
                $material
                    ->setVideo(null)
                    ->setStream(null)
                    ->setStreamStart(null);
                break;
            case $this->materialsServices::TYPE_VIDEO:
                if ($video) {
                    $material->setVideo($video);
                }
                $material
                    ->setAudio(null)
                    ->setStream(null)
                    ->setStreamStart(null);
                break;
            case $this->materialsServices::TYPE_STREAM:
                if (
                    !empty($data['stream_url']) &&
                    $material->getStream() != $data['stream_url'] &&
                    $data['is_stream_finished']
                ) {
                    throw new LogicException("Эфир завершен, нельзя редактировать ссылку");
                }
                if ($video) {
                    $material->setVideo($video);
                }
                $event_id = $this->facecastServices->getEventIdByUrl($data['stream_url']);
                $material
                    ->setAudio(null)
                    ->setStream($data['stream_url'])
                    ->setStreamStart($data['stream_start']->getTimestamp())
                    ->setStreamEventId($event_id)
                    ->setIsStreamFinished($data['is_stream_finished']);
                break;
            case $this->materialsServices::TYPE_MEDITATION:
                $category = $this->materialsCategoriesRepository->findOneBy(['code' => 'meditation']);
                if (!$category) {
                    throw new LogicException("Ошибка. Категория медитаций отсутствует");
                }
                if ($audio) {
                    $material->setAudio($audio);
                }
                $material
                    ->setVideo(null)
                    ->setStream(null)
                    ->setStreamStart(null);
                break;
        }

        if (empty($data['lazy_post'])) {
            $data['is_show_bill'] = null;
        }

        $material
            ->setTitle($data['title'])
            ->setDescription($data['description'])
            ->setShortDescription($data['short_description'])
            ->setPreviewImage($data['preview_image'])
            ->setAccess($data['access'])
            ->setLazyPost(empty($data['lazy_post']) ? null : $data['lazy_post']->getTimestamp())
            ->setIsShowBill($data['is_show_bill'])
            ->setCategory($category)
            ->setType($data['type'])
            ->setCreateTime(time());

        $this->em->persist($material);
        $this->em->flush();

        switch ($data['type']) {
            case $this->materialsServices::TYPE_AUDIO:
                $audio->setMaterials($material);
                $this->em->persist($audio);
                $this->em->flush();
                break;
            case $this->materialsServices::TYPE_VIDEO:
            case $this->materialsServices::TYPE_STREAM:
                if ($video) {
                    $video->setMaterials($material);
                    $this->em->persist($video);
                    $this->em->flush();
                }
                break;
            case $this->materialsServices::TYPE_ARTICLE:
                $this->updateArticleFiles($data['article_files'], $material);
                break;
            case $this->materialsServices::TYPE_MEDITATION:
                $category = $this->materialsCategoriesRepository->findOneBy(['code' => 'meditation']);
                if (!$category) {
                    throw new LogicException("Ошибка. Категория медитаций отсутствует");
                }
                $audio->setMaterials($material);
                $this->em->persist($audio);
                $this->em->flush();
                break;
        }

        $data['preview_image']->setMaterials($material);
        $this->em->persist($data['preview_image']);
        $this->em->flush();

        return $material;
    }

    /**
     * Удаление материала
     *
     * @param int $material_id
     *
     * @return Materials
     */
    public function deleteMaterial(int $material_id): Materials
    {
        if (empty($material_id)) {
            throw new LogicException("Нужно указать ID материала");
        }

        $material = $this->materialsRepository->find($material_id);
        if (!$material) {
            throw new LogicException("Материал не найден");
        }

        if ($material->getIsDeleted()) {
            throw new LogicException("Материал уже удален");
        }

        $material->setIsDeleted(true);
        $this->em->persist($material);
        $this->em->flush();

        return $material;
    }

    /**
     * Обновляем информацию о файлах
     *
     * @param mixed $files
     * @param mixed $material
     *
     * @return [type]
     */
    public function updateArticleFiles($files, $material)
    {
        // Получаем список фоток
        $exist_files = $this->filesRepository->findBy([
            'materials' => $material->getId(),
            'file_type' => FileServices::TYPE_MATERIAL_ARTICLE
        ]);
        if ($exist_files) {
            foreach ($exist_files as $item) {
                // Если фото нет в базе, то удаляем
                if (!in_array($item->getId(), $files)) {
                    $item->setMaterials(null);
                    $this->em->persist($item);
                    $this->em->flush();
                }
            }
        }

        foreach ($files as $file) {
            $find_file = $this->filesRepository->findOneBy([
                'id' => intval($file),
                'file_type' => FileServices::TYPE_MATERIAL_ARTICLE
            ]);
            if ($find_file && empty($find_file->getMaterials())) {
                $find_file->setMaterials($material);
                $this->em->persist($find_file);
                $this->em->flush();
            }
        }
    }

    /**
     * @param mixed $form
     *
     * @return [type]
     */
    private function getMaterialsData($form)
    {
        $video = $form->get('video_id')->getData() ?? null;
        $audio = $form->get('audio_id')->getData() ?? null;
        $preview_image = $form->get('preview_image_id')->getData();

        return [
            'material' => $form->get('material_id')->getData(),
            'title' => $form->get('title')->getData(),
            'description' => $form->get('description')->getData(),
            'short_description' => $form->get('short_description')->getData(),
            'access' => $form->get('access')->getData(),
            'type' => $form->get('type')->getData(),
            'lazy_post' => $form->get('lazy_post')->getData(),
            'is_show_bill' => $form->get('is_show_bill')->getData(),
            'category' => $form->get('category_id')->getData(),
            'audio' => $audio,
            'video' => $video,
            'article_files' => $form->get('article_files')->getData(),
            'stream_url' => $form->get('stream_url')->getData(),
            'stream_start' => $form->get('stream_start')->getData(),
            'preview_image' => $preview_image,
            'is_stream_finished' => $form->get('is_stream_finished')->getData() ?? false
        ];
    }
}
