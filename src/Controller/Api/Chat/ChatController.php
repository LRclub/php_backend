<?php

namespace App\Controller\Api\Chat;

use App\Controller\Base\BaseApiController;
use App\Exceptions\ChatAccessDenied;
use App\Services\User\UserServices;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Annotations as OA;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\Chat\ChatServices;
use App\Form\Chat\ChatMessageType;
use App\Services\QueueServices;
use Symfony\Component\Security\Core\User\UserInterface;

class ChatController extends BaseApiController
{
    /**
     * Получить список доступных чатов
     *
     * @Route("/api/chat", name="api_chat_list", methods={"GET"})
     *
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="chat")
     * @Security(name="Bearer")
     */
    public function getChatListAction(
        CoreSecurity $security,
        ChatServices $chatServices,
        UserServices $userServices
    ): Response {
        $user = $security->getUser();
        $user_is_vip = $userServices->userIsVip($user);

        $result = $chatServices->getChatList($user, $user_is_vip);

        return $this->jsonSuccess(['result' => $result]);
    }

    /**
     * Получить список сообщений чата
     *
     * @Route("/api/chat/messages", name="api_chat_messages", methods={"GET"})
     *
     * @OA\Parameter(in="query", name="chat_id",
     *               schema={"type"="integer", "example"="1"},
     *               description="ID чата"
     *              ),
     * @OA\Parameter(in="query", name="message_id",
     *               schema={"type"="integer", "example"="1"},
     *               description="ID последнего полученного сообщения"
     *              )
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="chat")
     * @Security(name="Bearer")
     */
    public function getChatMessages(
        CoreSecurity $security,
        Request $request,
        ChatServices $chatServices,
        UserServices $userServices
    ) {
        $user = $security->getUser();

        $chat_id = (int)$request->query->get('chat_id');
        $message_id = (int)$request->query->get('message_id');

        try {
            $this->checkAccess($chatServices, $userServices, $chat_id, $user);
        } catch (ChatAccessDenied $exception) {
            return $this->jsonError($exception->getMessage(), $exception->getCode());
        }

        $messages = $chatServices->getChatMessages($chat_id, $message_id, $user);

        return $this->jsonSuccess($messages);
    }

    /**
     * Отправить сообщение в чат
     *
     * @Route("/api/chat", name="api_chat_send", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="chat_id", type="integer", description="ID чата", example="1"),
     *       @OA\Property(
     *                  property="message",
     *                  type="text", description="Сообщение",
     *                   example="Привет чат"
     *      ),
     *       @OA\Property(property="files",
     *          type="array",
     *          description="Идентификаторы файлов",
     *          example="[1,2,4]",
     *          @OA\Items(type="integer", format="int32")
     *       ),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Сообщение успешно отправлено")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="chat")
     * @Security(name="Bearer")
     */
    public function sendMessageAction(
        CoreSecurity $security,
        Request $request,
        ChatServices $chatServices,
        QueueServices $queueServices,
        UserServices $userServices
    ) {
        $user = $security->getUser();

        $chat_id = (int)$this->getJson($request, 'chat_id');
        $message = (string)$this->getJson($request, 'message');
        $files = (array)$this->getJson($request, 'files');

        $chat = [
            'message_id' => null,
            'chat_id' => $chat_id,
            'message' => $message,
            'files' => $files
        ];

        $form = $this->createFormByArray(ChatMessageType::class, $chat);

        $result = [];
        if ($form->isValid()) {
            //проверяем на доступ юзера к чату
            try {
                $this->checkAccess($chatServices, $userServices, $chat_id, $user);
            } catch (ChatAccessDenied $exception) {
                return $this->jsonError($exception->getMessage(), $exception->getCode());
            }

            try {
                $message = $chatServices->sendMessage($user, $form);
                $queueServices->sendChat(ChatServices::CHAT_EVENT_SEND, $message->getId());
                $result['data'] = $chatServices->getMessageForSocket('send', $message);
            } catch (LogicException $e) {
                return $this->jsonError(['#' => $e->getMessage()]);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }
        return $this->jsonSuccess(['result' => $result]);
    }

    /**
     * Редактировать сообщение
     *
     * @Route("/api/chat", name="api_chat_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="chat_id", type="integer", description="ID чата", example="1"),
     *       @OA\Property(property="message_id", type="integer", description="ID сообщения", example="1"),
     *       @OA\Property(
     *                  property="message",
     *                  type="text", description="Сообщение",
     *                   example="Привет чат"
     *      ),
     *       @OA\Property(property="files",
     *          type="array",
     *          description="Идентификаторы файлов",
     *          example="[1,2,4]",
     *          @OA\Items(type="integer", format="int32")
     *       ),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Сообщение успешно отредактировано")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="chat")
     * @Security(name="Bearer")
     */
    public function updateMessageAction(
        CoreSecurity $security,
        Request $request,
        ChatServices $chatServices,
        QueueServices $queueServices
    ) {
        $user = $security->getUser();

        $chat = [
            'message_id' => (int)$this->getJson($request, 'message_id'),
            'chat_id' => (int)$this->getJson($request, 'chat_id'),
            'message' => (string)$this->getJson($request, 'message'),
            'files' => (array)$this->getJson($request, 'files')
        ];

        $form = $this->createFormByArray(ChatMessageType::class, $chat);
        $result = [];
        if ($form->isValid()) {
            try {
                $message = $chatServices->updateMessage($user, $form);
                $queueServices->sendChat(ChatServices::CHAT_EVENT_EDIT, $message->getId());
                $result['data'] = $chatServices->getMessageForSocket('edit', $message);
            } catch (LogicException $e) {
                return $this->jsonError(['user_id' => $e->getMessage()]);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => $result]);
    }

    /**
     * Удалить сообщение
     *
     * @Route("/api/chat/{id}", requirements={"id"="\d+"}, name="api_chat_delete", methods={"DELETE"})
     *
     * @OA\Parameter(name="id", in="path", description="ID сообщения",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Сообщение успешно удалено")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="chat")
     * @Security(name="Bearer")
     */
    public function deleteMessageAction(
        CoreSecurity $security,
        Request $request,
        ChatServices $chatServices,
        QueueServices $queueServices,
        int $id
    ) {
        $user = $security->getUser();

        $result = [];
        try {
            $message = $chatServices->deleteMessage($user, $id);
            $queueServices->sendChat(ChatServices::CHAT_EVENT_DELETE, $message->getId());
            $result['data'] = $chatServices->getMessageForSocket('delete', $message);
        } catch (LogicException $e) {
            return $this->jsonError(['user_id' => $e->getMessage()]);
        }

        return $this->jsonSuccess(['result' => $result]);
    }

    /**
     * Проверяем на доступ к чату
     *
     * @param ChatServices $chatServices
     * @param int $chat_id
     * @param bool $user_is_vip
     * @throws ChatAccessDenied
     */
    private function checkAccess(
        ChatServices $chatServices,
        UserServices $userServices,
        int $chat_id,
        UserInterface $user
    ) {
        if ($user->getIsSpecialRole()) {
            return ;
        }

        $chat = $chatServices->getChat($chat_id);
        $user_is_vip = $userServices->userIsVip($user);

        if (!$chat) {
            throw new ChatAccessDenied('Чат не найден', 404);
        }

        if ($chat->isVip() && !$user_is_vip) {
            throw new ChatAccessDenied('Доступ в чат только по VIP подписке!', 403);
        }
    }
}
