<?php

namespace App\Services\File;

use App\Entity\ChatMessage;
use App\Entity\Files;
use App\Entity\FeedbackMessage;
use App\Entity\Materials;
use App\Entity\User;
use App\Repository\FilesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Services\QueueServices;

class FileServices
{
    //тип изображения фото профиля
    public const TYPE_AVATAR = 'photo_profile';
    //изображения в профиле
    public const TYPE_ACCOUNT_PHOTO = 'public_photo';
    //изображение публичных документов
    public const TYPE_PUBLIC_DOCS = 'public_docs';
    //файл из чата
    public const TYPE_CHAT_DOCS = 'chat_docs';
    //файл из обратной связи
    public const TYPE_FEEDBACK_DOCS = 'feedback_docs';

    //превью материала
    public const TYPE_MATERIAL_PREVIEW = 'material_preview';
    //подкаст, аудиозапись, медитация материала
    public const TYPE_MATERIAL_AUDIO = 'material_audio';
    //видео материала
    public const TYPE_MATERIAL_VIDEO = 'material_video';
    //файлы для статьи в материалах
    public const TYPE_MATERIAL_ARTICLE = 'material_article';

    //аватар специалиста
    public const TYPE_SPECIALIST_AVATAR = 'specialist_avatar';

    //максимальные размеры картинок в пикселях
    public const MAX_SIZES_PIXEL = [
        self::TYPE_AVATAR => [300, 2000],
        self::TYPE_PUBLIC_DOCS => [300, 5000],
        self::TYPE_CHAT_DOCS => [300, 5000],
        self::TYPE_ACCOUNT_PHOTO => [300, 5000],
        self::TYPE_FEEDBACK_DOCS => [300, 5000],
        self::TYPE_MATERIAL_PREVIEW => [300, 5000],
        self::TYPE_MATERIAL_ARTICLE => [300, 5000],
        self::TYPE_SPECIALIST_AVATAR => [300, 5000]
    ];

    // максимальные размеры файла в байтах
    // 1024 = 1 кб
    public const MAX_SIZES = [
        self::TYPE_AVATAR => [1024, 15728640],              // 15728640 = 15мб
        self::TYPE_PUBLIC_DOCS => [1024, 15728640],         // 15728640 = 15мб
        self::TYPE_CHAT_DOCS => [1024, 15728640],           // 15728640 = 15мб
        self::TYPE_ACCOUNT_PHOTO => [1024, 15728640],       // 15728640 = 15мб
        self::TYPE_FEEDBACK_DOCS => [1024, 15728640],       // 15728640 = 15мб
        self::TYPE_MATERIAL_PREVIEW => [1024, 15728640],    // 15728640 = 15мб
        self::TYPE_MATERIAL_AUDIO => [1024, 1073741824],    // 1073741824 = 1гб
        self::TYPE_MATERIAL_VIDEO => [1024, 10737418240],   // 10737418240 = 10гб
        self::TYPE_MATERIAL_ARTICLE => [1024, 314572800],    // 15728640 = 15мб
        self::TYPE_SPECIALIST_AVATAR => [1024, 15728640],   // 15728640 = 15мб
    ];

    private array $available_types = [
        self::TYPE_AVATAR,
        self::TYPE_PUBLIC_DOCS,
        self::TYPE_CHAT_DOCS,
        self::TYPE_ACCOUNT_PHOTO,
        self::TYPE_FEEDBACK_DOCS,
        self::TYPE_MATERIAL_PREVIEW,
        self::TYPE_MATERIAL_AUDIO,
        self::TYPE_MATERIAL_VIDEO,
        self::TYPE_MATERIAL_ARTICLE,
        self::TYPE_SPECIALIST_AVATAR
    ];

    // Только загрузка фото
    private array $only_image_types = [
        self::TYPE_AVATAR,
        self::TYPE_PUBLIC_DOCS,
        self::TYPE_ACCOUNT_PHOTO,
        self::TYPE_MATERIAL_PREVIEW,
        self::TYPE_SPECIALIST_AVATAR
    ];

    // Только загрузка видео
    private array $only_video_types = [
        self::TYPE_MATERIAL_VIDEO,
    ];

    // Только загрузка аудио
    private array $only_audio_types = [
        self::TYPE_MATERIAL_AUDIO
    ];

    private EntityManagerInterface $em;
    private FilesRepository $filesRepository;
    private QueueServices $queueServices;

    public function __construct(
        EntityManagerInterface $em,
        FilesRepository $filesRepository,
        QueueServices $queueServices
    ) {
        $this->em = $em;
        $this->filesRepository = $filesRepository;
        $this->queueServices = $queueServices;
    }

    /**
     * Проверка валидности типа файла
     *
     * @param string $type
     * @return bool
     */
    public function isTypeValid(string $type)
    {
        return in_array($type, $this->available_types);
    }

    /**
     * Проверка можно ли загружать файлы
     *
     * @param string $type
     * @return bool
     */
    public function isTypeFile(string $type)
    {
        return in_array($type, $this->only_image_types);
    }

    /**
     * Проверка можно ли загружать аудио
     *
     * @param string $type
     * @return bool
     */
    public function isTypeAudio(string $type)
    {
        return in_array($type, $this->only_video_types);
    }

    /**
     * Проверка можно ли загружать файлы статьи
     *
     * @param string $type
     * @return bool
     */
    public function isTypeArticle(string $type)
    {
        return $type == self::TYPE_MATERIAL_ARTICLE;
    }

    /**
     * Проверка можно ли загружать видео
     *
     * @param string $type
     * @return bool
     */
    public function isTypeVideo(string $type)
    {
        return in_array($type, $this->only_audio_types);
    }

    /**
     * Возвращаем массив без левых идентификаторов
     *
     * @param UserInterface $user
     * @param array $ids
     * @return array
     */
    public function filterBrokenIds(UserInterface $user, array $ids): array
    {
        //если пустой массив, то запрос не шлем
        if (empty($ids)) {
            return [];
        }

        $result = $this->filesRepository->findByUserIdAndFileIds($user, $ids);

        return array_column($result, 'id');
    }

    /**
     * Информация по файлу. В случае если файл битый, то false
     *
     * @param UploadedFile $encodedFile
     * @return array|false
     */
    public function getImageSize(UploadedFile $encodedFile)
    {
        return @getimagesize($encodedFile->getPathInfo() . DIRECTORY_SEPARATOR . $encodedFile->getFilename());
    }

    /**
     * Информация по файлу. В случае если файл битый, то false
     *
     * @param UploadedFile $encodedFile
     * @return array|false
     */
    public function getFileSize(UploadedFile $encodedFile)
    {
        return filesize($encodedFile->getPathInfo() . DIRECTORY_SEPARATOR . $encodedFile->getFilename());
    }

    /**
     * Создаем запись о файле в базе
     *
     * @param UserInterface $user
     * @param string $filepath
     * @param string $file_type
     * @param int $is_image
     * @param int $is_video
     * @param mixed $is_audio
     *
     * @return Files
     */
    public function createFile(
        UserInterface $user,
        string $filepath,
        string $file_type,
        int $is_image,
        int $is_video,
        $is_audio
    ): Files {
        $file = new Files();
        $file->setUser($user)
            ->setIsDeleted(false)
            ->setCreateTime(time())
            ->setFileType($file_type)
            ->setFilePath($filepath)
            ->setIsImage($is_image)
            ->setIsAudio($is_audio)
            ->setIsVideo($is_video);

        $this->em->persist($file);
        $this->em->flush();

        return $file;
    }

    /**
     * Размер файла в кило/мега/гига/тера/пета байтах
     * @param int $filesize — размер файла в байтах
     *
     * @return string — возвращаем размер файла в Б, КБ, МБ, ГБ или ТБ
     */
    public function filesizeFormat($filesize)
    {
        $formats = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ']; // варианты размера файла
        $format = 0; // формат размера по-умолчанию

        // прогоняем цикл
        while ($filesize > 1024 && count($formats) != ++$format) {
            $filesize = round($filesize / 1024, 2);
        }

        // если число большое, мы выходим из цикла с
        // форматом превышающим максимальное значение
        // поэтому нужно добавить последний возможный
        // размер файла в массив еще раз
        $formats[] = 'ТБ';

        return $filesize . $formats[$format];
    }

    /**
     * Удаляем файл
     *
     * @param Files $file
     * @return bool
     */
    public function deleteFile(Files $file)
    {
        $file_path = '.' . DIRECTORY_SEPARATOR . $file->getFilePath();

        if (file_exists($file_path)) {
            $is_ok = unlink($file_path);

            if ($is_ok) {
                $file->setIsDeleted(true);
                $file->setDeleteTime(time());

                $this->em->persist($file);
                $this->em->flush();

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Проверка есть ли у сообщения message и создание нового файла
     *
     * @param User $user
     * @param Files $file
     * @param FeedbackMessage $message
     *
     * @return bool
     */
    public function isFeedbackMessageExist(User $user, Files $file, FeedbackMessage $message): bool
    {
        if (empty($file) || empty($message)) {
            return false;
        }

        if ($file->getFeedbackMessage() && $file->getFileType() == self::TYPE_FEEDBACK_DOCS) {
            $new_file = new Files();
            $new_file->setUser($user)
                ->setIsDeleted(false)
                ->setCreateTime(time())
                ->setFileType($file->getFileType())
                ->setFilePath($file->getFilePath())
                ->setIsImage($file->getIsImage())
                ->setFeedbackMessage($message);

            $this->em->persist($new_file);
            $this->em->flush();
            return true;
        }
        return false;
    }

    /**
     * Проверка статьи и сохранение файлов
     *
     * @param User $user
     * @param Files $file
     * @param Materials $materials
     *
     * @return bool
     */
    public function isMaterialsArticleExist(User $user, Files $file, Materials $materials): bool
    {
        if (empty($file) || empty($materials)) {
            return false;
        }

        if (
            !$file->getMaterials() && $file->getFileType() == self::TYPE_MATERIAL_ARTICLE
        ) {
            $new_file = new Files();
            $new_file->setUser($user)
                ->setIsDeleted(false)
                ->setCreateTime(time())
                ->setFileType($file->getFileType())
                ->setFilePath($file->getFilePath())
                ->setIsImage($file->getIsImage())
                ->setMaterials($materials);

            $this->em->persist($new_file);
            $this->em->flush();
            return true;
        }
        return false;
    }

    /**
     * Проверка существования чата и типа файла
     *
     * @param User $user
     * @param Files $file
     * @param ChatMessage $message
     *
     * @return bool
     */
    public function isChatMessageExist(User $user, Files $file, ChatMessage $message): bool
    {
        if (empty($file) || empty($message)) {
            return false;
        }

        if ($file->getChatMessage() && $file->getFileType() == self::TYPE_CHAT_DOCS) {
            $new_file = new Files();
            $new_file->setUser($user)
                ->setIsDeleted(false)
                ->setCreateTime(time())
                ->setFileType($file->getFileType())
                ->setFilePath($file->getFilePath())
                ->setIsImage($file->getIsImage())
                ->setChatMessage($message);

            $this->em->persist($new_file);
            $this->em->flush();
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param array $file
     *
     * @return [type]
     */
    public function validateFile(User $user, array $files)
    {
        if (!empty($files)) {
            foreach ($files as $file) {
                $find_file = $this->filesRepository->find(intval($file));
                if ($find_file) {
                    $file_owner = $find_file->getUser();
                    if ($file_owner->getId() != $user->getId()) {
                        // Проверка на админ права
                        if (!$user->getIsSpecialRole()) {
                            return ['error' => "Файл № " . $file . " Вам не принадлежит!"];
                        }
                    }
                } else {
                    return ['error' => "Файл № " . $file . " не найден!"];
                }
            }
        }
        return [];
    }

    /**
     * Проверка расширения видео, конвертация
     *
     * @param UploadedFile $file
     * @param Files $file_entity
     *
     * @return [type]
     */
    public function videoConvertation(UploadedFile $file, Files $file_entity)
    {
        $ext = $file->getClientOriginalExtension();
        $this->queueServices->videoFormatting($ext, $file_entity);

        return true;
    }

    /**
     * @return [type]
     */
    public function getAvailableTypes()
    {
        return $this->available_types;
    }
}
