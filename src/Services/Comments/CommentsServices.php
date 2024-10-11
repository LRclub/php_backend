<?php

namespace App\Services\Comments;

// Components
use App\Services\HelperServices;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
// Entity
use App\Entity\Comments;
use App\Entity\Likes;
use App\Entity\LikesCollector;
// Services
use App\Services\File\UserFileServices;
use App\Services\WebSocketMessagesServices;
use App\Services\QueueServices;
use App\Services\Notice\NoticeServices;
// Repository
use App\Repository\MaterialsRepository;
use App\Repository\LikesCollectorRepository;
use App\Repository\LikesRepository;
use App\Repository\CommentsRepository;
use App\Repository\CommentsCollectorRepository;
use App\Repository\NoticeRepository;

class CommentsServices
{
    // Флаг. Прошли комментарии модерацию по умолчанию?
    private const IS_COMMENTS_MODERATED = false;


    private EntityManagerInterface $em;
    private CoreSecurity $security;
    private UserFileServices $userFileServices;
    private WebSocketMessagesServices $webSocketMessagesServices;
    private CommentsCollectorRepository $commentsCollectorRepository;
    private LikesRepository $likesRepository;
    private CommentsRepository $commentsRepository;
    private MaterialsRepository $materialsRepository;
    private LikesCollectorRepository $likesCollectorRepository;
    private QueueServices $queueServices;
    private NoticeServices $noticeServices;
    private ParameterBagInterface $params;

    public function __construct(
        EntityManagerInterface $em,
        CoreSecurity $security,
        UserFileServices $userFileServices,
        WebSocketMessagesServices $webSocketMessagesServices,
        CommentsCollectorRepository $commentsCollectorRepository,
        CommentsRepository $commentsRepository,
        LikesRepository $likesRepository,
        MaterialsRepository $materialsRepository,
        LikesCollectorRepository $likesCollectorRepository,
        QueueServices $queueServices,
        NoticeServices $noticeServices,
        ParameterBagInterface $params
    ) {
        $this->em = $em;
        $this->security = $security;
        $this->webSocketMessagesServices = $webSocketMessagesServices;
        $this->userFileServices = $userFileServices;
        $this->commentsCollectorRepository = $commentsCollectorRepository;
        $this->commentsRepository = $commentsRepository;
        $this->likesRepository = $likesRepository;
        $this->materialsRepository = $materialsRepository;
        $this->likesCollectorRepository = $likesCollectorRepository;
        $this->queueServices = $queueServices;
        $this->noticeServices = $noticeServices;
        $this->params = $params;
    }

    /**
     * Создание комментария
     *
     * @param mixed $path
     *
     * @return [type]
     */
    public function createComment($form, $user)
    {
        $comment = new Comments();
        $likes_collector = new LikesCollector();

        $comments_collector = $form->get('comments_collector')->getData();
        $reply = $form->get('reply')->getData();
        $text = $form->get('text')->getData();

        $comment->setCreateTime(time());
        $material = $this->materialsRepository->findOneBy(['comments_collector' => $comments_collector->getId()]);
        if ($material) {
            $likes_collector->setMaterial($material);
        }

        $comment
            ->setUser($user)
            ->setCommentsCollector($comments_collector)
            ->setLikesCollector($likes_collector)
            ->setReply($reply)
            ->setModerationStatus(self::IS_COMMENTS_MODERATED)
            ->setText($text)
            ->setIsDeleted(false);

        $this->em->persist($likes_collector);
        $this->em->persist($comment);
        $this->em->flush();

        $likes_collector->setComment($comment);
        $this->em->persist($likes_collector);
        $this->em->flush();

        // Отправка уведомлений
        if (!empty($reply) && $user != $reply->getUser()) {
            // Создание notice
            $comment_text = HelperServices::cutText($reply->getText(), 72);
            $answer_text = HelperServices::cutText($text, 72);

            $message = "Вам ответили: \n" . trim(preg_replace('/\s+/', ' ', $answer_text));

            $this->noticeServices->createNoticeByArrayData($reply->getUser(), [
                'type' => NoticeRepository::TYPE_INFO,
                'message' => $message,
                'category' => NoticeRepository::CATEGORY_COMMENTS,
                'data' => [
                    'user_info' => $user->getUserProfileArrayData(),
                    'comment' => $comment_text,
                    'answer' => $answer_text,
                    'link' => $this->params->get('base.url') . '/panel/material/' . $material->getId()
                ]
            ]);

            // Отправка уведомления на почту
            if (
                $reply->getUser()->getNotifications() &&
                $reply->getUser()->getNotifications()->getEmailNotice() &&
                $reply->getUser()->getIsConfirmed()
            ) {
                $this->queueServices->sendEmail(
                    $reply->getUser()->getEmail(),
                    'Вам ответили в комментариях',
                    '/mail/user/notifications/email_notice.html.twig',
                    [
                        'user_info' => $user->getUserProfileArrayData(),
                        'comment' => $reply->getText(),
                        'answer' => $text,
                        'link' => $this->params->get('base.url') . '/panel/material/' . $material->getId()
                    ]
                );
            }
        }

        return $comment;
    }

    /**
     * Обновление комментария
     *
     * @param mixed $form
     * @param mixed $user
     *
     */
    public function updateComment($form)
    {
        $comment = $form->get('comment_id')->getData();
        $text = $form->get('text')->getData();
        $comment->setUpdateTime(time())->setText($text)->setModerationStatus(self::IS_COMMENTS_MODERATED);

        $this->em->persist($comment);
        $this->em->flush();

        return $comment;
    }

    /**
     * Удаление комментария
     *
     * @param mixed $comment_id
     * @param mixed $user
     *
     */
    public function deleteComment($comment_id, $user)
    {
        $comment = $this->commentsRepository->find($comment_id);
        if (!$comment) {
            throw new LogicException('Комментарий не найден');
        }

        if ($comment->getUser() != $user) {
            if (!$user->getIsModerator() && !$user->getIsAdmin()) {
                throw new LogicException('Комментарий принадлежит другому пользователю');
            }
        }

        if ($comment->getIsDeleted()) {
            throw new LogicException('Комментарий уже удален');
        }

        $comment->setIsDeleted(true)->setUpdateTime(time());
        $this->em->persist($comment);
        $this->em->flush();

        return $comment;
    }

    /**
     * Восстановление админом комментария
     *
     * @param mixed $comment_id
     * @param mixed $user
     *
     */
    public function adminReturnComment($comment_id, $user)
    {
        $comment = $this->commentsRepository->find($comment_id);
        if (!$comment) {
            throw new LogicException('Комментарий не найден');
        }

        if (!$user->getIsModerator() && !$user->getIsAdmin()) {
            throw new LogicException('У Вас нет прав');
        }


        if (!$comment->getIsDeleted()) {
            throw new LogicException('Комментарий активен');
        }

        $comment->setIsDeleted(false)->setUpdateTime(time());
        $this->em->persist($comment);
        $this->em->flush();

        return $comment;
    }

    /**
     * Поставить/убрать лайк
     *
     * @param mixed $comment_id
     * @param mixed $user
     *
     * @return bool
     */
    public function likeComment($likes_collector_id, $like_status, $user): bool
    {
        $likes_collector = $this->likesCollectorRepository->find($likes_collector_id);
        if (!$likes_collector) {
            throw new LogicException('like collector id не найдено');
        }

        $is_liked = $this->likesRepository->findOneBy([
            'likes_collector' => $likes_collector->getId(),
            'user' => $user->getId()
        ]);

        if (empty($is_liked)) {
            $like = new Likes();
            $like->setUser($user)->setLikesCollector($likes_collector)->setIsLike($like_status);
            $this->em->persist($like);
            $this->em->flush();
            return true;
        }

        if ($is_liked) {
            $is_liked->setIsLike($like_status);
            $this->em->persist($is_liked);
            $this->em->flush();
        }

        if ($likes_collector->getComment()) {
            $this->queueServices->sendComments('like', $likes_collector->getComment()->getId());
        } else {
            $this->queueServices->sendEvents('likes', $is_liked->getLikesCollector()->getMaterial()->getId());
        }

        return $like_status;
    }

    /**
     * Получение списка комментариев
     *
     * @param mixed $page
     * @param mixed $collector_id
     *
     * @return [type]
     */
    public function getComments($comment_id, $collector_id)
    {
        $limit = $this->commentsRepository::PAGE_OFFSET;
        $result = [];
        $comments = $this->commentsRepository->getComments($comment_id, $collector_id, $limit);
        if (!$comments) {
            return ['comments' => $result, 'can_load' => false];
        }
        $result = $this->getCommentsData($comments, $limit, $comment_id);
        $can_load = $this->commentsRepository->getComments(end($result)['id'], $collector_id, $limit, true);
        $can_load = !empty(intval($can_load));
        return ['comments' => $result, 'can_load' => $can_load];
    }

    /**
     * Получение списка ответов на комментарий
     *
     * @param null $last_comment_id
     * @param mixed $collector_id
     * @param mixed $comment_id
     *
     * @return [type]
     */
    public function getReply($last_comment_id, $collector_id, $comment_id)
    {
        $limit = $this->commentsRepository::PAGE_OFFSET;
        $result = [];
        $comments = $this->commentsRepository->getReply($last_comment_id, $collector_id, $comment_id, $limit);
        if (!$comments) {
            return ['comments' => $result, 'can_load' => false];
        }
        $result = $this->getCommentsData($comments, $limit, $last_comment_id);
        $can_load = $this->commentsRepository->getReply(end($result)['id'], $collector_id, $comment_id, $limit, true);
        $can_load = !empty(intval($can_load));
        return ['comments' => $result, 'can_load' => $can_load];
    }

    /**
     * @param mixed $comments
     * @param mixed $limit
     * @param null $last_comment_id
     *
     * @return [type]
     */
    private function getCommentsData($comments, $limit, $last_comment_id = null)
    {
        $user = $this->security->getUser();
        $result = [];
        foreach ($comments as $comment) {
            $result[] = [
                'id' => $comment->getId(),
                'comment_collector_id' => $comment->getCommentsCollector()->getId(),
                'likes_collector_id' => $comment->getLikesCollector()->getId(),
                'likes_count' => $this->likesRepository->count([
                    'likes_collector' => $comment->getLikesCollector()->getId(),
                    'is_like' => true
                ]),
                'is_liked' => $this->likesRepository->getIsLiked($comment->getLikesCollector()->getId(), $user),
                'text' => $comment->getIsDeleted() ? '' : $comment->getText(),
                'is_deleted' => $comment->getIsDeleted(),
                'create_time' => $comment->getCreateTime(),
                'update_time' => $comment->getUpdateTime(),
                'is_edited' => !empty($comment->getUpdateTime()),
                'can_edit' => $comment->getUser() == $user ? true : false,
                'user_id' => $comment->getUser()->getId(),
                'first_name' => $comment->getUser()->getFirstName(),
                'last_name' => $comment->getUser()->getLastName(),
                'avatar' => $this->userFileServices->getAvatar($comment->getUser()),
                'is_admin' => $comment->getUser()->getIsAdmin(),
                'is_editor' => $comment->getUser()->getIsEditor(),
                'is_moderator' => $comment->getUser()->getIsModerator(),
                'can_load' => (int)$this->commentsRepository->getReply(
                    null,
                    $comment->getCommentsCollector()->getId(),
                    $comment->getId(),
                    $limit,
                    true
                ) > $this->commentsRepository::PAGE_OFFSET,
                'child' => $this->getReply(null, $comment->getCommentsCollector()->getId(), $comment->getId())
            ];
        }

        return $result;
    }

    /**
     * Нормализация данных для отправки в сокет
     *
     * @param mixed $action
     * @param mixed $comment
     *
     * @return [type]
     */
    public function sendWebSocketChatComment($action, $comment)
    {
        $result = $this->getMessageForSocket($action, $comment);
        $result['code'] = $this->webSocketMessagesServices->encodePublish($result);

        $data[] = [
            'url' => $this->webSocketMessagesServices::COMMENTS_URL .
                $comment->getCommentsCollector()->getId() . '/' . $action,
            'result' => $result
        ];

        return $this->webSocketMessagesServices->socketSendActionMessage($data);
    }

    /**
     * Нормализация данных для отправки в сокет
     *
     * @param mixed $action
     * @param mixed $comment
     *
     * @return [type]
     */
    public function getMessageForSocket($action, $comment)
    {
        $result = [
            'date' => $comment->getCreateTime(),
            'data' => [
                'id' => $comment->getId(),
                'action' => $action,
                'comment_collector_id' => $comment->getCommentsCollector()->getId(),
                'likes_collector_id' => $comment->getLikesCollector()->getId(),
                'likes_count' => $this->likesRepository->count([
                    'likes_collector' => $comment->getLikesCollector()->getId(),
                    'is_like' => true
                ]),
                'text' => $comment->getIsDeleted() ? '' : $comment->getText(),
                'is_deleted' => $comment->getIsDeleted(),
                'reply_id' => $comment->getReply() ? $comment->getReply()->getId() : null,
                'create_time' => $comment->getCreateTime(),
                'update_time' => $comment->getUpdateTime(),
                'is_edited' => !empty($comment->getUpdateTime()),
                'user_id' => $comment->getUser()->getId(),
                'first_name' => $comment->getUser()->getFirstName(),
                'last_name' => $comment->getUser()->getLastName(),
                'avatar' => $this->userFileServices->getAvatar($comment->getUser()),
                'is_admin' => $comment->getUser()->getIsAdmin(),
                'is_editor' => $comment->getUser()->getIsEditor(),
                'is_moderator' => $comment->getUser()->getIsModerator()
            ]
        ];

        return $result;
    }

    /**
     * Отправка ивента добавления комментария
     *
     * @param mixed $action
     * @param mixed $comment
     *
     * @return [type]
     */
    public function sendWebSocketEvent($action, $material)
    {
        $count = 0;
        switch ($action) {
            case 'likes':
                $count = $this->likesRepository->count([
                    'likes_collector' => $material->getLikesCollector()->getId(),
                    'is_like' => true
                ]);
                break;
            case 'comments':
                $count = $this->commentsRepository->count([
                    'comments_collector' => $material->getCommentsCollector()->getId()
                ]);
                break;
            default:
                return false;
        }

        $result = [
            'date' => $material->getCreateTime(),
            'data' => [
                'id' => $material->getId(),
                'views_count' => $material->getViewsCount(),
                'count' => $count,
                'create_time' => $material->getCreateTime(),
            ]
        ];
        $result['code'] = $this->webSocketMessagesServices->encodePublish($result);

        $data[] = [
            'url' => $this->webSocketMessagesServices::EVENTS_URL .
                $material->getId() .
                '/' . $action,
            'result' => $result
        ];

        return $this->webSocketMessagesServices->socketSendActionMessage($data);
    }
}
