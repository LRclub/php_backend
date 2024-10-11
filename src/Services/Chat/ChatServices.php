<?php

namespace App\Services\Chat;

// Entity
use App\Entity\Chat;
use App\Entity\ChatUnreadCount;
use App\Entity\User;
use App\Entity\ChatMessage;
// Repository
use App\Repository\ChatRepository;
use App\Repository\ChatMessageRepository;
use App\Repository\FilesRepository;
use App\Repository\ChatUnreadCountRepository;
use App\Repository\UserRepository;
use App\Repository\NoticeRepository;
// Services
use App\Services\File\FileServices;
use App\Services\File\UserFileServices;
use App\Services\TwigServices;
use App\Services\WebSocketMessagesServices;
use App\Services\QueueServices;
// Symfony
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ChatServices
{
    public const CHAT_EVENT_SEND = 'send';
    public const CHAT_EVENT_EDIT = 'edit';
    public const CHAT_EVENT_DELETE = 'delete';

    private EntityManagerInterface $em;
    private CoreSecurity $security;
    private ChatRepository $chatRepository;
    private ChatMessageRepository $chatMessageRepository;
    private FileServices $fileServices;
    private FilesRepository $filesRepository;
    private UserFileServices $userFileServices;
    private WebSocketMessagesServices $webSocketMessagesServices;
    private QueueServices $queueServices;
    private ChatUnreadCountRepository $chatUnreadCountRepository;

    public function __construct(
        EntityManagerInterface $em,
        CoreSecurity $security,
        ChatRepository $chatRepository,
        ChatMessageRepository $chatMessageRepository,
        FileServices $fileServices,
        FilesRepository $filesRepository,
        UserFileServices $userFileServices,
        WebSocketMessagesServices $webSocketMessagesServices,
        QueueServices $queueServices,
        ChatUnreadCountRepository $chatUnreadCountRepository
    ) {
        $this->em = $em;
        $this->security = $security;
        $this->chatRepository = $chatRepository;
        $this->chatMessageRepository = $chatMessageRepository;
        $this->fileServices = $fileServices;
        $this->filesRepository = $filesRepository;
        $this->userFileServices = $userFileServices;
        $this->webSocketMessagesServices = $webSocketMessagesServices;
        $this->queueServices = $queueServices;
        $this->chatUnreadCountRepository = $chatUnreadCountRepository;
    }

    /**
     * Возвращаем информацию по идентификатору чата
     *
     * @param int $chat_id
     * @return Chat|null
     */
    public function getChat(int $chat_id): ?Chat
    {
        return $this->chatRepository->findOneBy(['id' => $chat_id]);
    }

    /**
     * Получение списка доступных чатов пользователя
     *
     * @param UserInterface $user
     * @param bool $user_is_vip
     * @return array [type]
     */
    public function getChatList(UserInterface $user, bool $user_is_vip = false)
    {
        $result = [];
        $chats = $this->chatRepository->getChatList($user);
        if (!$chats) {
            return $result;
        }

        foreach ($chats as $chat) {
            // Получение последнего сообщения чата
            $last_message = $this->chatMessageRepository->findOneBy(['chat' => $chat->getId()], ['id' => 'DESC']);
            $last_message_array = null;
            if ($last_message) {
                $last_message_array = $last_message->getChatMessageArrayData();
            }

            $unread = $this->chatUnreadCountRepository->findOneBy(['user' => $user, 'chat' => $chat->getId()]);
            if (!$unread) {
                $unread_messages_count = 0;
            } else {
                $unread_messages_count = $this->chatMessageRepository->getUnreadMessagesCount($unread);
            }

            $available = $user->getIsSpecialRole() || !$chat->isVip() || ($chat->isVip() && $user_is_vip);
            $result[] = [
                'id' => $chat->getId(),
                'title' => $chat->getTitle(),
                'type' => $chat->getType(),
                'is_vip' => $chat->isVip(),
                'available' => $available,
                'preview_image' => $chat->getPreviewImage(),
                'last_chat_message' => $available ? $last_message_array : null,
                'unread_messages_count' => $unread_messages_count
            ];
        }

        return $result;
    }

    /**
     * Получить список сообщений чата
     *
     * @param mixed $chat_id
     * @param mixed $page
     * @param mixed $user
     *
     * @return [type]
     */
    public function getChatMessages($chat_id, $message_id, $user)
    {
        $result = [];
        $messages = $this->chatMessageRepository->getMessages($message_id, $chat_id, $user);
        if (!$messages) {
            return [
                'messages' => [],
                'can_load' => false
            ];
        }
        $last_message_id = null;
        foreach ($messages as $key => $message) {
            $files = $this->filesRepository->findBy(['chat_message' => $message->getId()]);
            $result[$key] = [
                'id' => $message->getId(),
                'chat_id' => $message->getChat()->getId(),
                'user_id' => $message->getUser()->getId(),
                'first_name' => $message->getUser()->getFirstName(),
                'last_name' => $message->getUser()->getLastName(),
                'message' => $message->getIsDeleted() ? '' : $message->getMessage(),
                'is_deleted' => $message->getIsDeleted(),
                'create_time' => $message->getCreateTime(),
                'update_time' => $message->getUpdateTime(),
                'is_edited' => !empty($message->getUpdateTime()),
                'can_edit' => $message->getUser() == $user ? true : false,
                'avatar' => $this->userFileServices->getAvatar($message->getUser()) ?? null,
                'is_admin' => $message->getUser()->getIsAdmin(),
                'is_editor' => $message->getUser()->getIsEditor(),
                'is_moderator' => $message->getUser()->getIsModerator(),
                'files' => null
            ];

            if ($files) {
                foreach ($files as $file) {
                    $result[$key]['files'][] = $file->getFileAsArray();
                }
            }

            $last_message_id = $message->getId();
        }

        $this->setIsReadChatMessages($user, $chat_id);

        return [
            'messages' => $result,
            'can_load' => $this->chatMessageRepository->canLoadMessages($last_message_id, $chat_id)
        ];
    }

    /**
     * @param User $user
     * @param int $chat_id
     *
     * @return [type]
     */
    public function setIsReadChatMessages(User $user, int $chat_id)
    {
        $chat = $this->chatRepository->find($chat_id);
        $last_message = $this->chatMessageRepository->findOneBy(array(), array('id' => 'DESC'));
        // Если сообщений в чате нет
        if (empty($last_message)) {
            return false;
        }

        $unread = $this->chatUnreadCountRepository->findOneBy(['user' => $user, 'chat' => $chat_id]);

        if (!$unread) {
            // Создаем новую запись
            $unread = new ChatUnreadCount();
            $unread
                ->setUser($user)
                ->setChat($chat);
        }

        $unread
            ->setLastMessage($last_message)
            ->setUpdateTime(time());
        $this->em->persist($unread);
        $this->em->flush();

        return true;
    }

    /**
     * Отправить сообщение в чат
     *
     * @param User $user
     * @param FormInterface $form
     * @param bool $is_admin
     *
     * @return [type]
     */
    public function sendMessage(User $user, FormInterface $form)
    {
        $chat = $form->get('chat_id')->getData();
        $files = $form->get('files')->getData();
        $chat_message = $form->get('message')->getData() ?? "";

        // Валидация файлов перед сохранением
        $validate = $this->fileServices->validateFile($user, $files);

        if (!empty($validate)) {
            throw new LogicException($validate['error']);
        }

        $message = new ChatMessage();
        $message->setChat($chat)->setUser($user)->setMessage($chat_message)->setCreateTime(time());
        if ($user->getIsModerator() || $user->getIsAdmin()) {
            $message->setIsAdmin(true);
        }

        $this->em->persist($message);
        $this->em->flush();

        foreach ($files as $file) {
            $find_file = $this->filesRepository->find(intval($file));
            if (!$this->fileServices->isChatMessageExist($user, $find_file, $message)) {
                $find_file->setChatMessage($message);
                $this->em->persist($find_file);
                $this->em->flush();
            }
        }

        // Есть ли у пользователя счетчик
        $user_counts = $this->chatUnreadCountRepository->findOneBy(['user' => $user, 'chat' => $chat]);
        if ($user_counts) {
            $user_counts->setLastMessage($message)->setUpdateTime(time());
            $this->em->persist($user_counts);
            $this->em->flush();
        }

        return $message;
    }

    /**
     * Редактировать сообщение в чате
     *
     * @param User $user
     * @param FormInterface $form
     *
     * @return [type]
     */
    public function updateMessage(User $user, FormInterface $form)
    {
        $chat = $form->get('chat_id')->getData();
        $files = $form->get('files')->getData();
        $chat_message = $form->get('message')->getData() ?? "";
        $message = $form->get('message_id')->getData();

        // Валидация файлов перед сохранением
        $validate = $this->fileServices->validateFile($user, $files);

        if (!empty($validate)) {
            throw new LogicException($validate['error']);
        }

        if ($user->getIsModerator() || $user->getIsAdmin()) {
            if ($user != $message->getUser()) {
                $user = $message->getUser();
            } else {
                $message->setUpdateTime(time());
            }
        } else {
            $message->setUpdateTime(time());
        }

        if ($user->getId() != $message->getUser()->getId() || $message->getIsDeleted()) {
            throw new LogicException("Запрещено редактировать данное сообщение");
        }

        $message->setChat($chat)->setMessage($chat_message)->setCreateTime(time());

        $this->em->persist($message);
        $this->em->flush();

        $this->updateMessageFiles($files, $message, $user);

        return $message;
    }

    /**
     * Удаление сообщения
     *
     * @param mixed $user
     * @param mixed $message_id
     *
     * @return [type]
     */
    public function deleteMessage($user, $message_id)
    {
        $message = $this->chatMessageRepository->findOneBy(['id' => $message_id, 'is_deleted' => false]);
        if (!$message) {
            throw new LogicException("Сообщение не найдено");
        }

        if ($user->getIsModerator() || $user->getIsAdmin()) {
            $user = $message->getUser();
        } else {
            $message->setUpdateTime(time());
        }

        if ($user->getIsModerator() || $user->getIsAdmin()) {
            $user = $message->getUser();
        } else {
            $message->setUpdateTime(time());
        }

        if ($user->getId() != $message->getUser()->getId()) {
            throw new LogicException("Запрещено удалять данное сообщение");
        }

        $message->setIsDeleted(true);
        $this->em->persist($message);
        $this->em->flush();

        return $message;
    }

    /**
     * Поиск чата
     *
     * @param mixed $chat_id
     *
     * @return [type]
     */
    public function getChatById($chat_id)
    {
        return $this->chatRepository->find((int)$chat_id);
    }

    /**
     * Обновляем информацию о файлах
     *
     * @param User $user
     * @param FormInterface $form
     * @return User
     */
    public function updateMessageFiles($files, $message, $user)
    {
        // Получаем список фоток
        $exist_files = $this->filesRepository->findBy(['chat_message' => $message->getId()]);
        if ($exist_files) {
            foreach ($exist_files as $item) {
                // Если фото нет в базе, то удаляем
                if (!in_array($item->getId(), $files)) {
                    $item->setChatMessage(null);
                    $this->em->persist($item);
                    $this->em->flush();
                }
            }
        }

        foreach ($files as $file) {
            $find_file = $this->filesRepository->find(intval($file));
            if (empty($find_file->getChatMessage())) {
                $find_file->setChatMessage($message);
                $this->em->persist($find_file);
                $this->em->flush();
            }
        }
    }

    /**
     * Нормализация данных для отправки в сокет
     *
     * @param string $action
     * @param ChatMessage $message
     * @param bool $is_last
     *
     * @return [type]
     */
    public function sendWebSocketChatMessage(string $action, ChatMessage $message, bool $is_last)
    {
        $result = $this->getMessageForSocket($action, $message);
        $result['code'] = $this->webSocketMessagesServices->encodePublish($result);

        $data[] = [
            'url' => $this->webSocketMessagesServices::CHAT_URL .
                $message->getChat()->getId() .
                '/' . $action,
            'result' => $result
        ];

        // Уведомление для списка сообщений в чате
        if ($is_last) {
            $data[] = [
                'url' => $this->webSocketMessagesServices::CHAT_URL .
                    $message->getChat()->getId() . '/' .
                    $this->webSocketMessagesServices::CHAT_LISTEN,
                'result' => $result
            ];
        }


        return $this->webSocketMessagesServices->socketSendActionMessage($data);
    }

    /**
     * Нормализация сообщения перед отправкой в чат
     *
     * @param mixed $action
     * @param mixed $message
     *
     * @return [type]
     */
    public function getMessageForSocket($action, $message)
    {
        $result = [];
        $files = $this->filesRepository->findBy(['chat_message' => $message->getId()]);
        $result = [
            'date' => $message->getCreateTime(),
            'data' => [
                'action' => $action,
                'id' => $message->getId(),
                'chat_id' => $message->getChat()->getId(),
                'user_id' => $message->getUser()->getId(),
                'first_name' => $message->getUser()->getFirstName(),
                'last_name' => $message->getUser()->getLastName(),
                'message' => $message->getIsDeleted() ? '' : $message->getMessage(),
                'is_deleted' => $message->getIsDeleted(),
                'create_time' => $message->getCreateTime(),
                'update_time' => $message->getUpdateTime(),
                'is_edited' => !empty($message->getUpdateTime()),
                'avatar' => $this->userFileServices->getAvatar($message->getUser()) ?? null,
                'is_admin' => $message->getUser()->getIsAdmin(),
                'is_editor' => $message->getUser()->getIsEditor(),
                'is_moderator' => $message->getUser()->getIsModerator(),
                'files' => null
            ]
        ];

        if ($files) {
            foreach ($files as $file) {
                $result['data']['files'][] = $file->getFileAsArray();
            }
        }

        return $result;
    }

    /**
     * @param int $message_id
     * @param int $chat_id
     * @param array $users_id
     *
     * @return [type]
     */
    public function updateLastUnreadMessage(int $message_id, int $chat_id, array $users_id)
    {
        $message = $this->chatMessageRepository->find($message_id);
        if (!$message) {
            return false;
        }

        $chat = $this->chatRepository->find($chat_id);
        if (!$chat) {
            return false;
        }

        foreach ($users_id as $id => $val) {
            $last_unread = $this->chatUnreadCountRepository->findOneBy([
                'user' => $id,
                'chat' => $chat_id
            ]);

            if (!$last_unread) {
                return false;
            }

            if ($last_unread->getLastMessage()->getId() != $message_id) {
                $last_unread->setLastMessage($message)->setUpdateTime(time());
                $this->em->persist($last_unread);
                $this->em->flush();
            }
        }
    }

    /**
     * Получение уведомления о непрочитанных сообщениях
     *
     * @param mixed $user
     *
     * @return [type]
     */
    public function getUnreadChatMessagesNotice($user)
    {
        $notice = [];
        $unread = $this->chatUnreadCountRepository->findBy(['user' => $user]);
        if (!$unread) {
            return [];
        }

        foreach ($unread as $value) {
            $last_message = $this->chatMessageRepository->findOneBy([
                'chat' => $value->getChat()
            ], array('id' => 'DESC'));

            if ($last_message && $last_message->getId() != $value->getLastMessage()->getId()) {
                $count = $this->chatMessageRepository->getUnreadMessagesCount($value);

                if ($count) {
                    $message = "У вас " . TwigServices::plural(
                        $count,
                        ['непрочитанное сообщение', 'непрочитанных сообщения', 'непрочитанных сообщений']
                    );
                    $notice[] = [
                        'id' => -$value->getChat()->getId(),
                        'type' => NoticeRepository::TYPE_INFO,
                        'title' => 'Чат',
                        'message' => $message,
                        'data' => ['chat' => $value->getChat()->getTitle()],
                        'category' => NoticeRepository::CATEGORY_CHAT,
                        'create_time' => time(),
                    ];
                }
            }
        }

        return $notice;
    }

    /**
     * Прочитать все уведомления в чате
     *
     * @param mixed $user
     *
     * @return [type]
     */
    public function setIsReadChatMessagesNotice($user)
    {
        $unread = $this->chatUnreadCountRepository->findBy(['user' => $user]);
        if (!$unread) {
            return [];
        }

        foreach ($unread as $value) {
            $last_message = $this->chatMessageRepository->findOneBy([
                'chat' => $value->getChat()
            ], array('id' => 'DESC'));
            if ($last_message && $last_message->getId() != $value->getLastMessage()->getId()) {
                $value->setLastMessage($last_message)->setUpdateTime(time());
                $this->em->persist($value);
                $this->em->flush();
            }
        }

        return true;
    }
}
