<?php

namespace App\Services\File;

use App\Repository\FilesRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

class UserFileServices
{
    private FilesRepository $filesRepository;
    private EntityManagerInterface $em;

    public function __construct(
        FilesRepository $filesRepository,
        EntityManagerInterface $em
    ) {
        $this->filesRepository  = $filesRepository;
        $this->em = $em;
    }

    public function setAvatar(UserInterface $user, $avatar)
    {
        $last_avatar = $this->filesRepository->getAvatar($user);
        if ($last_avatar && $avatar->getId() != $last_avatar->getId()) {
            $last_avatar->setIsActive(false)->setIsDeleted(true);
            $this->em->persist($last_avatar);
        }

        $avatar->setIsActive(true);
        $this->em->persist($avatar);
        $this->em->flush();
    }

    public function getAvatar(UserInterface $user)
    {
        $avatar = $this->filesRepository->getAvatar($user);
        if ($avatar) {
            return $avatar->getFileAsArray();
        }

        return null;
    }

    public function getFilesByType(UserInterface $user, string $type)
    {
        $files = $this->filesRepository->getFilesByType($user, $type);
        $files_array = [];
        if ($files) {
            foreach ($files as $file) {
                $files_array[] = $file->getFileAsArray();
            }
        }

        return $files_array;
    }

    /**
     * @param UserInterface $user
     * @param string $type
     * @param array $images_ids
     *
     * @return [type]
     */
    public function setFilesByType(UserInterface $user, string $type, array $images_ids)
    {
        $files_ids = $this->filesRepository->getArrayIdsByType($user, $type);
        if ($files_ids) {
            $this->filesRepository->setUnactiveFilesByIds($files_ids);
        }

        $this->filesRepository->setActiveFilesByIds($images_ids);

        $this->em->flush();
    }
}
