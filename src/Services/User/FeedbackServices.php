<?php

namespace App\Services\User;

use App\Entity\EmailConfirmation;
use App\Entity\Feedback;
use App\Entity\FeedbackMessage;
use App\Entity\User;
use App\Repository\EmailConfirmationRepository;
use App\Services\HelperServices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use App\Repository\FilesRepository;
use App\Repository\FeedbackRepository;
use App\Repository\FeedbackMessageRepository;
use App\Services\File\FileServices;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Services\WebSocketMessagesServices;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Services\File\UserFileServices;

class FeedbackServices
{
    private EntityManagerInterface $em;
    private EmailConfirmationRepository $emailConfirmationRepository;
    private FilesRepository $filesRepository;
    private FeedbackRepository $feedbackRepository;
    private FeedbackMessageRepository $feedbackMessageRepository;
    private FileServices $fileServices;
    private EventDispatcherInterface $eventDispatcher;
    private CoreSecurity $security;
    private WebSocketMessagesServices $webSocketMessagesServices;
    private UserFileServices $userFileServices;

    public function __construct(
        EntityManagerInterface $em,
        EmailConfirmationRepository $emailConfirmationRepository,
        FilesRepository $filesRepository,
        FeedbackRepository $feedbackRepository,
        FeedbackMessageRepository $feedbackMessageRepository,
        FileServices $fileServices,
        EventDispatcherInterface $eventDispatcher,
        CoreSecurity $security,
        WebSocketMessagesServices $webSocketMessagesServices,
        UserFileServices $userFileServices
    ) {
        $this->em = $em;
        $this->emailConfirmationRepository = $emailConfirmationRepository;
        $this->filesRepository = $filesRepository;
        $this->feedbackRepository = $feedbackRepository;
        $this->feedbackMessageRepository = $feedbackMessageRepository;
        $this->fileServices = $fileServices;
        $this->eventDispatcher = $eventDispatcher;
        $this->security = $security;
        $this->webSocketMessagesServices = $webSocketMessagesServices;
        $this->userFileServices = $userFileServices;
    }

    /**
     * Сохраняем / Обновляем запрос
     *
     * @param User $user
     *
     * @return EmailConfirmation|null
     */
    public function saveFeedback(User $user, FormInterface $form, $is_admin = false): ?FeedbackMessage
    {
        $title = $form->get('title')->getData();
        $message_feedback = $form->get('message')->getData() ?? "";
        $message = new FeedbackMessage();
        $files = $form->get('files')->getData();
        $feedback_id = $form->get('feedback_id')->getData();

        // Валидация файлов перед сохранением
        if ($is_admin) {
            $validate = $this->fileServices->validateFile($this->security->getUser(), $files);
        } else {
            $validate = $this->fileServices->validateFile($user, $files);
        }

        if (!empty($validate)) {
            throw new LogicException($validate['error']);
        }

        if (!empty($feedback_id)) {
            $feedback = $this->feedbackRepository->find($feedback_id);
            if (!$feedback) {
                throw new LogicException("Чат не существует");
            }

            if ($feedback->getStatus()) {
                throw new LogicException("Чат закрыт");
            }

            $feedback->setUpdateTime(time());
        } else {
            //Save feedback
            $feedback = new Feedback();
            $feedback->setTitle($title)->setStatus(false)->setCreateTime(time())->setUpdateTime(time())->setUser($user);
            $this->em->persist($feedback);
            $this->em->flush();
        }

        // если пользователь не админ, проверяем кто создал feedback и может оставить сообщение
        if (!$user->getIsAdmin() && !$user->getIsModerator()) {
            if ($feedback->getUser()->getId() != $user->getId()) {
                throw new LogicException("Вы не можете оставлять сообщение в этом чате");
            }
        } else {
            if ($feedback->getUser()->getId() != $user->getId()) {
                $message->setIsAdmin(true);
            }
        }

        $message_feedback = HelperServices::prepareText($message_feedback);

        $message
            ->setFeedback($feedback)
            ->setUser($user)
            ->setComment($message_feedback)
            ->setCreateTime(time())
            ->setUpdateTime(time());

        if ($is_admin) {
            $message->setUser($this->security->getUser())->setIsAdmin(true);
        }

        $this->em->persist($message);
        $this->em->flush();

        foreach ($files as $file) {
            $find_file = $this->filesRepository->find(intval($file));
            if (!$this->fileServices->isFeedbackMessageExist($user, $find_file, $message)) {
                $find_file->setFeedbackMessage($message);
                $this->em->persist($find_file);
                $this->em->flush();
            }
        }

        return $message;
    }

    /**
     * Редактирование сообщения
     *
     * @param mixed $user
     * @param mixed $form
     *
     * @return [type]
     */
    public function editFeedback($user, $form, $is_admin = false): ?FeedbackMessage
    {
        $message_feedback = $form->get('message')->getData() ?? "";
        $files = $form->get('files')->getData();
        $feedback_message = $form->get('feedback_message_id')->getData();

        // Валидация файлов перед сохранением
        if ($is_admin) {
            $validate = $this->fileServices->validateFile($this->security->getUser(), $files);
        } else {
            $validate = $this->fileServices->validateFile($user, $files);
        }

        if (!empty($validate)) {
            throw new LogicException($validate['error']);
        }

        if ($feedback_message->getIsDeleted()) {
            throw new LogicException("Сообщение удалено");
        }

        if ($feedback_message->getFeedback()->getStatus()) {
            throw new LogicException("Чат закрыт");
        }

        $feedback_message->getFeedback()->setUpdateTime(time());

        // если пользователь не админ, проверяем кто создал feedback и может оставить сообщение
        if (!$user->getIsAdmin() && !$user->getIsModerator()) {
            if ($feedback_message->getUser()->getId() != $user->getId()) {
                throw new LogicException("Вы не можете редактировать это сообщение");
            }
        }

        $message_feedback = HelperServices::prepareText($message_feedback);

        $feedback_message
            ->setComment($message_feedback)
            ->setUpdateTime(time());

        if ($is_admin) {
            $feedback_message->setUser($this->security->getUser())->setIsAdmin(true);
        }

        $this->updateMessageFiles($files, $feedback_message);

        $this->em->persist($feedback_message);
        $this->em->flush();

        return $feedback_message;
    }

    /**
     * Обновление заголовка
     * @param mixed $user
     * @param mixed $form
     *
     * @return [type]
     */
    public function updateTitle($user, $form)
    {
        $title = $form->get('title')->getData();
        $feedback = $form->get('feedback_id')->getData();

        if (
            (!$user->getIsAdmin() && !$user->getIsModerator()) &&
            $feedback->getUser()->getId() != $user->getId()
        ) {
            throw new LogicException("Не удалось сменить заголовок для данного обращения");
        }

        $feedback->setTitle($title);
        $feedback->setUpdateTime(time());
        $this->em->persist($feedback);
        $this->em->flush();

        return true;
    }

    /**
     * Получаем обращение
     *
     * @param User $user
     * @param mixed $feedback_id
     * @param bool $last_message
     *
     * @return [type]
     */
    public function getFeedback(User $user, $feedback_id, $last_message = false)
    {
        $result = [];
        if ($user->getIsAdmin()) {
            $feedback = $this->feedbackRepository->findOneBy(['id' => $feedback_id]);
        } else {
            $feedback = $this->feedbackRepository->findOneBy(['id' => $feedback_id, 'user' => $user->getId()]);
        }

        if (empty($feedback)) {
            return $result;
        }

        $query = [
            'feedback' => $feedback->getId(),
        ];

        if (!$user->getIsSpecialRole()) {
            $query['is_deleted'] = 0;
        }

        $limit = $last_message ? 1 : null;
        $sort = $last_message ? 'DESC' : 'ASC';

        $messages = $this->feedbackMessageRepository->findBy($query, ['id' => $sort], $limit);
        $message_count = $this->feedbackMessageRepository->count($query);


        $result['feedbackInfo'] = [
            'feedback_id' => $feedback->getId(),
            'user_id' => $feedback->getUser()->getId(),
            'first_name' => $feedback->getUser()->getFirstName(),
            'last_name' => $feedback->getUser()->getLastName(),
            'is_admin' => $feedback->getUser()->getIsAdmin(),
            'is_moderator' => $feedback->getUser()->getIsModerator(),
            'is_editor' => $feedback->getUser()->getIsEditor(),
            'patronymic_name' => $feedback->getUser()->getPatronymicName(),
            'title' => $feedback->getTitle(),
            'status' => $feedback->getStatus(),
            'create_time' => $feedback->getCreateTime(),
            'update_time' => $feedback->getUpdateTime(),
            'message_count' => $message_count,
            'messages_unread' => count(
                $this->feedbackMessageRepository->findUnreadFeedbackMessages(
                    $feedback->getId(),
                    $user
                )
            ),
        ];

        if ($messages) {
            foreach ($messages as $key => $message) {
                $files = $this->filesRepository->findBy(['feedback_message' => $message]);
                $user_answer_name = $message->getUser()->getFirstName();
                $user_answer_last_name = $message->getUser()->getLastName();
                $user_answer_id = $message->getUser()->getId();

                if ($message->getIsAdmin()) {
                    $user_answer_name = 'Администратор';
                    $user_answer_last_name = '';
                }

                $result['feedbackInfo']['messages'][$key] = $message->getFeedbackMessageArrayData();
                $result['feedbackInfo']['messages'][$key] += [
                    'user_id' => $user_answer_id,
                    'first_name' => $user_answer_name,
                    'last_name' => $user_answer_last_name,
                    'files' => null,
                    'is_admin' => $message->getUser()->getIsAdmin(),
                    'is_editor' => $message->getUser()->getIsEditor(),
                    'is_moderator' => $message->getUser()->getIsModerator(),
                    'avatar' => $this->userFileServices->getAvatar($message->getUser()),
                ];

                if ($files) {
                    foreach ($files as $file) {
                        $result['feedbackInfo']['messages'][$key]['files'][] = $file->getFileAsArray();
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param User $user
     * @param int $closed
     * @param int $page
     * @param array $order_by
     * @param string $search
     *
     * @return [type]
     */
    public function getFeedbackRequests(
        User $user,
        int $closed,
        int $page,
        array $order_by = [],
        string $search = ""
    ) {
        $offset = ($page - 1) * $this->feedbackRepository::PAGE_OFFSET;
        $limit = $this->feedbackRepository::PAGE_OFFSET;

        if ($user->getIsAdmin()) {
            if (empty($order_by)) {
                $order_by = ['sort_param' => "", 'sort_type' => "desc"];
            }

            $result_total_count = (int)$this->feedbackRepository->getAdminAllCategories(
                $closed,
                $order_by,
                $limit,
                $offset,
                $search,
                true
            );
            $result_closed_count = $this->feedbackRepository->count([
                'status' => $this->feedbackRepository::FEEDBACK_CLOSED
            ]);
            $result_opened_count = $this->feedbackRepository->count([
                'status' => $this->feedbackRepository::FEEDBACK_OPENED
            ]);
            $requests = $this->feedbackRepository->getAdminAllCategories(
                $closed,
                $order_by,
                $limit,
                $offset,
                $search
            );
        } else {
            $result_total_count = $this->feedbackRepository->count([
                'user' => $user->getId(), 'status' => $closed
            ]);
            $result_closed_count = $this->feedbackRepository->count([
                'user' => $user->getId(), 'status' => $this->feedbackRepository::FEEDBACK_CLOSED
            ]);
            $result_opened_count = $this->feedbackRepository->count([
                'user' => $user->getId(), 'status' => $this->feedbackRepository::FEEDBACK_OPENED
            ]);
            $requests = $this->feedbackRepository->findBy([
                'user' => $user->getId(), 'status' => $closed
            ], ['update_time' => 'DESC'], $limit, $offset);
        }

        $result['result'] = [];
        $result['result_total_count'] = $result_total_count;
        $result['offset'] = $this->feedbackRepository::PAGE_OFFSET;
        $result['result_closed_count'] = $result_closed_count;
        $result['result_opened_count'] = $result_opened_count;
        $result['pages'] = ceil($result_total_count / $this->feedbackRepository::PAGE_OFFSET);

        foreach ($requests as $key => $request) {
            $result['result'][$key] = $this->getFeedback($user, $request->getId(), true);
        }

        return $result;
    }

    /**
     * Удаление сообщения в чате с поддержкой
     *
     * @param User $user
     * @param int $message_id
     *
     * @return FeedbackMessage
     */
    public function deleteFeedbackMessage(User $user, int $message_id): FeedbackMessage
    {
        $message = $this->feedbackMessageRepository->find($message_id);
        if (!$message) {
            throw new LogicException("Сообщение не найдено");
        }

        if ($message->getIsDeleted()) {
            throw new LogicException("Сообщение уже удалено");
        }

        $is_first_message = $this->feedbackMessageRepository->findOneBy([
            'feedback' => $message->getFeedback()->getId()
        ], ['id' => 'ASC']);

        if ($is_first_message->getId() == $message->getId()) {
            throw new LogicException("Нельзя удалить первое сообщение");
        }

        if (!$user->getIsAdmin()) {
            if ($message->getUser() != $user) {
                throw new LogicException("Вы не можете удалить это сообщение");
            }
        }

        $message->setIsDeleted(true)->setUpdateTime(time());
        $this->em->persist($message);
        $this->em->flush();

        return $message;
    }

    /**
     * Обновляем информацию о файлах
     *
     * @param User $user
     * @param FormInterface $form
     * @return User
     */
    public function updateMessageFiles($files, $message)
    {
        // Получаем список фоток
        $exist_files = $this->filesRepository->findBy(['feedback_message' => $message->getId()]);
        if ($exist_files) {
            foreach ($exist_files as $item) {
                // Если фото нет в базе, то удаляем
                if (!in_array($item->getId(), $files)) {
                    $item->setFeedbackMessage(null);
                    $this->em->persist($item);
                    $this->em->flush();
                }
            }
        }

        foreach ($files as $file) {
            $find_file = $this->filesRepository->find(intval($file));
            if (empty($find_file->getChatMessage())) {
                $find_file->setFeedbackMessage($message);
                $this->em->persist($find_file);
                $this->em->flush();
            }
        }
    }

    /**
     * Нормализация данных для отправки в сокет
     *
     * @param mixed $action
     * @param mixed $comment
     *
     * @return [type]
     */
    public function sendWebSocketFeedbackMessage($action, $message)
    {
        $result = $this->getWebSocketFeedbackMessage($action, $message);
        $result['code'] = $this->webSocketMessagesServices->encodePublish($result);

        $data[] = [
            'url' => $this->webSocketMessagesServices::FEEDBACK_URL .
                $message->getFeedback()->getId() . '/' . $action,
            'result' => $result
        ];

        return $this->webSocketMessagesServices->socketSendActionMessage($data);
    }

    /**
     * Нормализация данных перед отправкой в сокет
     *
     * @param mixed $action
     * @param mixed $message
     *
     * @return [type]
     */
    public function getWebSocketFeedbackMessage($action, $message)
    {
        $files = $this->filesRepository->findBy(['feedback_message' => $message]);
        $user_answer_name = $message->getUser()->getFirstName();
        $user_answer_last_name = $message->getUser()->getLastName();
        $user_answer_id = $message->getUser()->getId();

        if ($message->getIsAdmin()) {
            $user_answer_name = 'Администратор';
            $user_answer_last_name = '';
        }

        $result['date'] = $message->getCreateTime();

        $result['data'] = $message->getFeedbackMessageArrayData();
        $result['data'] += [
            'action' => $action,
            'user_id' => $user_answer_id,
            'first_name' => $user_answer_name,
            'last_name' => $user_answer_last_name,
            'files' => null,
            'is_admin' => $message->getUser()->getIsAdmin(),
            'is_editor' => $message->getUser()->getIsEditor(),
            'is_moderator' => $message->getUser()->getIsModerator(),
            'avatar' => $this->userFileServices->getAvatar($message->getUser()),
        ];

        if ($files) {
            foreach ($files as $file) {
                $result['data']['files'][] = $file->getFileAsArray();
            }
        }

        return $result;
    }
}
