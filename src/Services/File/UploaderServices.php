<?php

namespace App\Services\File;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploaderServices
{
    //форматы фото
    public const EXTENSIONS_IMAGES = ['png', 'jpg', 'jpeg', 'webp'];
    //форматы видео
    public const EXTENSIONS_VIDEO = ['mp4', 'webm', 'ogg', 'mkv'];
    //форматы аудио
    public const EXTENSIONS_AUDIO = ['mp3', 'ogg', 'wav'];
    //форматы для статьи
    public const EXTENSIONS_ARTICLES = ['pdf', 'doc', 'mp3', 'mp4', 'png', 'jpg', 'jpeg', 'webp'];
    //форматы файлов
    public const EXTENSIONS_FILES = ['pdf', 'doc', 'docx', 'xlsx', 'xls', 'pdf', 'txt', 'zip', 'csv'];
    private SluggerInterface $slugger;
    private ParameterBagInterface $params;
    private FileServices $fileServices;

    public function __construct(SluggerInterface $slugger, ParameterBagInterface $params, FileServices $fileServices)
    {
        $this->slugger = $slugger;
        $this->params  = $params;
        $this->fileServices  = $fileServices;
    }

    /**
     * Загрузка файла
     *
     * @param UploadedFile $file
     * @param string $file_type
     * @return false|string
     * @throws \Exception
     */
    public function upload(UploadedFile $file, string $file_type)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->getClientOriginalExtension();

        if (!$this->fileServices->isTypeValid($file_type)) {
            throw new FileException('Неизвестная группа файла!');
        }

        try {
            $file->move($this->getTargetDirectory($file_type), $fileName);
        } catch (FileException $e) {
            if ($this->params->get('kernel.environment') !== 'prod') {
                throw new \Exception('Ошибка при загрузке файла: ' . $e->getMessage());
            }

            return null;
        }

        return $this->getTargetDirectory($file_type) . $fileName;
    }

    /**
     * Проверяем на валидность разрешение
     *
     * @param UploadedFile $file
     * @return bool
     */
    public function isValid(UploadedFile $file)
    {
        return in_array(
            strtolower(
                $file->getClientOriginalExtension()
            ),
            array_merge(
                self::EXTENSIONS_IMAGES,
                self::EXTENSIONS_FILES,
                self::EXTENSIONS_AUDIO,
                self::EXTENSIONS_VIDEO
            )
        );
    }

    /**
     * Файл - фото?
     *
     * @param UploadedFile $file
     *
     * @return bool
     */
    public function isImage(UploadedFile $file): bool
    {
        return in_array(strtolower($file->getClientOriginalExtension()), self::EXTENSIONS_IMAGES);
    }


    /**
     * Файл - аудио?
     *
     * @param UploadedFile $file
     *
     * @return bool
     */
    public function isAudio(UploadedFile $file): bool
    {
        return in_array(strtolower($file->getClientOriginalExtension()), self::EXTENSIONS_AUDIO);
    }

    /**
     * Файл для статьи материала?
     *
     * @param UploadedFile $file
     *
     * @return bool
     */
    public function isArticle(UploadedFile $file): bool
    {
        return in_array(strtolower($file->getClientOriginalExtension()), self::EXTENSIONS_ARTICLES);
    }

    /**
     * Файл - видео?
     *
     * @param UploadedFile $file
     *
     * @return bool
     */
    public function isVideo(UploadedFile $file): bool
    {
        return in_array(strtolower($file->getClientOriginalExtension()), self::EXTENSIONS_VIDEO);
    }

    /**
     * Возвращаем путь
     * @param string $file_type
     * @return string
     */
    private function getTargetDirectory(string $file_type)
    {
        $path = trim($this->params->get('file.directory_upload'), " \t\n\r\0\x0B/\\.");

        return $path . '/' . $file_type . '/' . date('Y') . '/' . date('m') . '/' . date('d') . '/';
    }
}
