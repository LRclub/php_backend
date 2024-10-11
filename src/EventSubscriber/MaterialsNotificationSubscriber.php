<?php

namespace App\EventSubscriber;

// Events

use App\Entity\Materials;
use App\Event\MaterialsNotificationEvent;
// Repository
use App\Repository\UserRepository;
use App\Repository\NoticeRepository;
// Services
use App\Services\HelperServices;
use App\Services\QueueServices;
use App\Services\Notice\NoticeServices;
// Components
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;

class MaterialsNotificationSubscriber implements EventSubscriberInterface
{
    private ParameterBagInterface $params;
    private QueueServices $queueServices;
    private KernelInterface $kernel;
    private UserRepository $userRepository;
    private NoticeServices $noticeServices;
    private EntityManagerInterface $em;

    public function __construct(
        KernelInterface $kernel,
        QueueServices $queueServices,
        ParameterBagInterface $params,
        UserRepository $userRepository,
        NoticeServices $noticeServices,
        EntityManagerInterface $em
    ) {
        $this->kernel = $kernel;
        $this->queueServices = $queueServices;
        $this->params = $params;
        $this->userRepository = $userRepository;
        $this->noticeServices = $noticeServices;
        $this->em = $em;
    }

    public static function getSubscribedEvents()
    {
        return [
            MaterialsNotificationEvent::NOTIFICATION_MATERIAL_NEW => 'newMaterials',
            MaterialsNotificationEvent::NOTIFICATION_STREAM_START => 'streamStart'
        ];
    }

    /**
     * Уведомление пользователей о новом материале
     *
     * @param MaterialsNotificationEvent $event
     *
     * @return [type]
     */
    public function newMaterials(MaterialsNotificationEvent $event)
    {
        $material = $event->getMaterial();
        $users = $this->getUsers($material);
        if (!$users) {
            return false;
        }

        $link = $this->params->get('base.url') . '/panel/material/' . $material->getId();
        foreach ($users as $user) {
            $subject = "Доступен новый материал: \n" . HelperServices::cutText($material->getTitle(), 72);

            // Создание notice
            $this->noticeServices->createNoticeByArrayData($user, [
                'type' => NoticeRepository::TYPE_INFO,
                'message' => $subject,
                'category' => NoticeRepository::CATEGORY_MATERIALS,
                'data' => ['link' => $link]
            ]);

            if ($user->getNotifications() && $user->getNotifications()->getNewMaterials() && $user->getIsConfirmed()) {
                $this->queueServices->sendEmail(
                    $user->getEmail(),
                    $subject,
                    '/mail/user/notifications/new_material.html.twig',
                    [
                        'title' => $material->getTitle(),
                        'category' => $material->getCategory()->getName(),
                        'link' => $link
                    ]
                );
            }
        }

        $material->setIsNotificationSended(true);
        $this->em->persist($material);
        $this->em->flush();
    }

    /**
     * Уведомление о начале эфира
     *
     * @param MaterialsNotificationEvent $event
     *
     * @return [type]
     */
    public function streamStart(MaterialsNotificationEvent $event)
    {
        $material = $event->getMaterial();
        $users = $this->getUsers($material);
        if (!$users) {
            return false;
        }

        $link = $this->params->get('base.url') . '/panel/material/' . $material->getId();
        foreach ($users as $user) {
            $subject = "Сегодня ожидается прямая трансляция. Не пропусти!";

            // Создание notice
            $this->noticeServices->createNoticeByArrayData($user, [
                'type' => NoticeRepository::TYPE_INFO,
                'message' => $subject,
                'category' => NoticeRepository::CATEGORY_STREAM,
                'data' => ['link' => $link]
            ]);

            if ($user->getNotifications() && $user->getNotifications()->getNewMaterials() && $user->getIsConfirmed()) {
                $this->queueServices->sendEmail(
                    $user->getEmail(),
                    $subject,
                    '/mail/user/notifications/new_stream.html.twig',
                    [
                        'title' => $material->getTitle(),
                        'category' => $material->getCategory()->getName(),
                        'link' => $link,
                        'start_stream' => date('Y-m-d H:i:s', $material->getStreamStart())
                    ]
                );
            }
        }

        $material->setStreamNotificationSended(true);
        $this->em->persist($material);
        $this->em->flush();
    }

    /**
     * Получение пользователей с подпиской
     *
     * @return [type]
     */
    private function getUsers(Materials $material)
    {
        $users = $this->userRepository->getUsersWithActiveSubscription($material);
        if (!$users) {
            return [];
        }

        return $users;
    }
}
