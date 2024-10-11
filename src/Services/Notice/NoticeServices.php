<?php

namespace App\Services\Notice;

use App\Entity\Notice;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NoticeRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\ChatUnreadCountRepository;
use App\Services\Chat\ChatServices;

class NoticeServices
{
    private EntityManagerInterface $em;
    private NoticeRepository $noticeRepository;
    private ChatServices $chatServices;

    public function __construct(
        EntityManagerInterface $em,
        NoticeRepository $noticeRepository,
        ChatServices $chatServices
    ) {
        $this->em = $em;
        $this->noticeRepository = $noticeRepository;
        $this->chatServices = $chatServices;
    }

    public function getUnreadNoticeById(int $notice_id)
    {
        return $this->noticeRepository->find($notice_id);
    }

    public function getUnreadNoticesAll($user)
    {
        $result = $this->chatServices->getUnreadChatMessagesNotice($user);

        $notices = $this->noticeRepository->findBy(['user' => $user->getId(), 'is_read' => 0]);
        if ($notices) {
            foreach ($notices as $notice) {
                $result[] = [
                    'id' => $notice->getId(),
                    'type' => $notice->getType(),
                    'title' => $notice->getCategoryTitle(),
                    'message' => $notice->getMessage(),
                    'data' => $notice->getData(),
                    'category' => $notice->getCategory(),
                    'create_time' => $notice->getCreateTime()
                ];
            }
        }

        if ($result) {
            rsort($result);
        }

        return $result;
    }

    /**
     * Прочитать одно уведомление
     *
     * @param mixed $notice_id
     *
     * @return [type]
     */
    public function setIsReadNotice($notice)
    {
        $notice->setIsRead(true);
        $this->em->persist($notice);
        $this->em->flush();
    }

    /**
     * Прочитать все уведомления
     *
     * @param mixed $notices
     *
     * @return [type]
     */
    public function setIsReadNoticesAll($user)
    {
        $this->noticeRepository->readAllNotices($user);
    }

    /**
     * Создать уведомление с помощью формы
     *
     * @param UserInterface $user
     * @param object $form
     *
     * @return [type]
     */
    public function createNotice(UserInterface $user, object $form)
    {
        $notice = new Notice();
        $notice->setType($form->get('type')->getData())
            ->setMessage($form->get('message')->getData())
            ->setCategory($form->get('category')->getData())
            ->setData($form->get('data')->getData())
            ->setCreateTime(time())
            ->setUser($user);
        $this->em->persist($notice);
        $this->em->flush();

        return true;
    }

    /**
     * Создать уведомление
     *
     * @param UserInterface $user
     * @param array $notice_data
     *
     * @return [type]
     */
    public function createNoticeByArrayData(UserInterface $user, array $notice_data)
    {
        $notice = new Notice();
        $notice->setType($notice_data['type'])
            ->setMessage($notice_data['message'])
            ->setCategory($notice_data['category'])
            ->setData($notice_data['data'])
            ->setCreateTime(time())
            ->setUser($user);

        $this->em->persist($notice);
        $this->em->flush();

        return true;
    }
}
