<?php

namespace App\Controller\Api\Feedback;

// Controller
use App\Controller\Base\BaseApiController;
// Services
use App\Services\User\FeedbackServices;
use App\Services\QueueServices;
use App\Services\Admin\AdminServices;
use App\Services\Messages\MessagesServices;
// Form
use App\Form\Feedback\FeedbackCreateType;
use App\Form\Feedback\FeedbackTitleType;
use App\Form\Feedback\FeedbackEditType;
// Symfony
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
// Etc
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;

class FeedbackController extends BaseApiController
{
    /**
     * Создание заявки обратной связи
     *
     * @Route("/api/feedback", name="api_feedback", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(
     *                  property="title",
     *                  type="string", description="Заголовок",
     *                  example="Какие документы требуются для модерации?"
     *      ),
     *       @OA\Property(
     *                  property="message",
     *                  type="text", description="Сообщение",
     *                  example="Предоставил паспорт, но модерацию не прошел"
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
     * @OA\Response(response=200, description="Обращение успешно создано")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="feedback")
     * @Security(name="Bearer")
     */
    public function createUserFeedbackAction(
        CoreSecurity $security,
        Request $request,
        FeedbackServices $feedbackServices
    ): Response {
        $user = $security->getUser();

        $feedback['title'] = (string)$this->getJson($request, 'title');
        $feedback['message'] = (string)$this->getJson($request, 'message');
        $feedback['files'] = (array)$this->getJson($request, 'files');

        $form = $this->createFormByArray(FeedbackCreateType::class, $feedback);
        if ($form->isValid()) {
            try {
                $feedbackServices->saveFeedback($user, $form);
            } catch (LogicException $e) {
                return $this->jsonError(['#' => $e->getMessage()]);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => true]);
    }

    /**
     * Редактирование заголовка заявки
     *
     * @Route("/api/feedback", name="api_feedback_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(
     *                  property="title",
     *                  type="string", description="Заголовок",
     *                  example="Какие документы требуются для модерации?"
     *      ),
     *       @OA\Property(property="feedback_id", type="integer", description="feedback_id", example="1")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Заголовок успешно обновлен")
     * @OA\Response(response=400, description="Обращение закрыто")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="feedback")
     * @Security(name="Bearer")
     */
    public function updateFeedbackTitleAction(
        CoreSecurity $security,
        Request $request,
        FeedbackServices $feedbackServices
    ): Response {
        $user = $security->getUser();

        $feedback['title'] = (string)$this->getJson($request, 'title');
        $feedback['feedback_id'] = (int)$this->getJson($request, 'feedback_id');

        $form = $this->createFormByArray(FeedbackTitleType::class, $feedback);
        if ($form->isValid()) {
            try {
                $feedbackServices->updateTitle($user, $form);
            } catch (LogicException $e) {
                return $this->jsonError(['user_id' => $e->getMessage()], 403);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => true]);
    }

    /**
     * Отправить сообщение
     *
     * @Route("/api/feedback/message", name="api_feedback_message_send", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="feedback_id", type="integer", description="ID чата", example="39"),
     *       @OA\Property(
     *                  property="message",
     *                  type="text", description="Сообщение",
     *                   example="Предоставил паспорт, но модерацию не прошел"
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
     * @OA\Tag(name="feedback message")
     * @Security(name="Bearer")
     */
    public function sendFeedbackMessageAction(
        CoreSecurity $security,
        Request $request,
        FeedbackServices $feedbackServices,
        QueueServices $queueServices
    ): Response {
        $user = $security->getUser();
        $feedback = [
            'feedback_id' => (int)$this->getJson($request, 'feedback_id'),
            'message' => (string)$this->getJson($request, 'message'),
            'files' => (array)$this->getJson($request, 'files')
        ];

        $form = $this->createFormByArray(FeedbackCreateType::class, $feedback);
        $is_admin = false;
        if ($form->isValid()) {
            try {
                $message = $feedbackServices->saveFeedback($user, $form);
                $is_admin = $message->getUser()->getIsAdmin() ?? false;
                $queueServices->sendFeedbackMessage('send', $message->getId());
                $result = $feedbackServices->getWebSocketFeedbackMessage('send', $message);
            } catch (LogicException $e) {
                return $this->jsonError(['feedback_id' => $e->getMessage()]);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }
        return $this->jsonSuccess(['result' => $result, 'is_admin' => $is_admin]);
    }

    /**
     * Редактировать сообщение
     *
     * @Route("/api/feedback/message", name="api_feedback_message_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="feedback_message_id", type="integer", description="ID сообщения", example="39"),
     *       @OA\Property(
     *                  property="message",
     *                  type="text", description="Сообщение",
     *                   example="Предоставил паспорт, но модерацию не прошел"
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
     * @OA\Response(response=200, description="Сообщение успешно изменено")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="feedback message")
     * @Security(name="Bearer")
     */
    public function editFeedbackMessageAction(
        CoreSecurity $security,
        Request $request,
        FeedbackServices $feedbackServices,
        QueueServices $queueServices
    ): Response {
        $user = $security->getUser();
        $feedback = [
            'feedback_message_id' => (int)$this->getJson($request, 'feedback_message_id'),
            'message' => (string)$this->getJson($request, 'message'),
            'files' => (array)$this->getJson($request, 'files')
        ];

        $form = $this->createFormByArray(FeedbackEditType::class, $feedback);
        $is_admin = false;

        if ($form->isValid()) {
            try {
                $message = $feedbackServices->editFeedback($user, $form);
                $is_admin = $message->getIsAdmin() ?? false;
                $queueServices->sendFeedbackMessage('edit', $message->getId());
                $result = $feedbackServices->getWebSocketFeedbackMessage('edit', $message);
            } catch (LogicException $e) {
                return $this->jsonError(['feedback_message_id' => $e->getMessage()]);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => $result, 'is_admin' => $is_admin]);
    }

    /**
     * Удалить сообщение
     *
     * @Route("/api/feedback/message", name="api_feedback_message_delete", methods={"DELETE"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="message_id", type="integer", description="ID сообщения", example="1")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Сообщение успешно удалено")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="feedback message")
     * @Security(name="Bearer")
     */
    public function deleteUserFeedbackMessageAction(
        CoreSecurity $security,
        Request $request,
        FeedbackServices $feedbackServices,
        QueueServices $queueServices
    ): Response {
        $user = $security->getUser();
        $message_id = (int)$this->getJson($request, 'message_id');
        $is_admin = false;

        try {
            $message = $feedbackServices->deleteFeedbackMessage($user, $message_id);
            $is_admin = $user->getIsAdmin() ?? false;
            $queueServices->sendFeedbackMessage('delete', $message->getId());
            $result = $feedbackServices->getWebSocketFeedbackMessage('delete', $message);
        } catch (LogicException $e) {
            return $this->jsonError(['message_id' => $e->getMessage()]);
        }

        return $this->jsonSuccess(['result' => $result, 'is_admin' => $is_admin]);
    }

    /**
     * Получение информации о заявке обратной связи
     *
     * @Route("/api/feedback/{id}",
     *          name="api_get_feedback", methods={"GET"},
     *          requirements={"id"="\d+"}
     *        )
     *
     * @OA\Parameter(name="id", in="path", description="ID заявки",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=400, description="Пустой id feedback")
     *
     * @OA\Tag(name="feedback")
     * @Security(name="Bearer")
     */
    public function getUserFeedbackAction(
        CoreSecurity $security,
        Request $request,
        FeedbackServices $feedbackServices,
        MessagesServices $messagesServices,
        int $id
    ): Response {
        $user = $security->getUser();
        if (empty($id)) {
            return $this->jsonError(['id' => 'Нужно указать ID заявки'], 400);
        }

        $result = $feedbackServices->getFeedback($user, $id);

        // Сообщения прочитаны
        $messagesServices->setIsReadMessageFeedback($id, $user);

        return $this->jsonSuccess(['result' => $result]);
    }

    /**
     * Список обращений в обратную связь у пользователя
     *
     * @Route(path="/api/feedback/requests/", name="api_user_feedback_requests", methods={"GET"})
     *
     * @OA\Get(path="/api/feedback/requests?", operationId="getFeedbackRequestsAction"),
     *
     * @OA\Parameter(
     *              in="query", name="page",
     *               schema={"type"="integer", "example"=1},
     *              description="Номер страницы. По умолчанию = 1"
     *              ),
     * @OA\Parameter(
     *              in="query", name="closed",
     *              schema={"type"="boolean", "example"=false},
     *              description="Статус обращения. Можно не указывать, по умолчанию false"
     *              )
     *
     * @OA\Response(response=200, description="Заявки получены")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=401, description="Необходима авторизация")
     *
     * @OA\Tag(name="feedback")
     * @Security(name="Bearer")
     */
    public function getFeedbackRequestsAction(
        CoreSecurity $security,
        Request $request,
        FeedbackServices $feedbackServices
    ) {
        $user = $security->getUser();
        $result = [];
        $page = $this->getIntOrNull($request->query->get('page')) ?? 1;
        $closed = intval(filter_var($request->query->get('closed'), FILTER_VALIDATE_BOOLEAN));

        $result = $feedbackServices->getFeedbackRequests($user, $closed, $page);

        return $this->jsonSuccess(['result' => $result['result'], 'resultTotalCount' => $result['result_total_count']]);
    }

    /**
     * Закрыть обращение в обратную связь
     *
     * @Route("/api/feedback/{id}", requirements={"id"="\d+"}, name="api_feedback_close", methods={"DELETE"})
     *
     * @OA\Parameter(name="id", in="path", description="ID заявки",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Обращение успешно закрыто")
     * @OA\Response(response=400, description="Обращение не существует либо закрыто")
     *
     * @OA\Tag(name="feedback")
     * @OA\Tag(name="admin feedback")
     * @Security(name="Bearer")
     */
    public function closeFeedbackAction(
        Request $request,
        CoreSecurity $security,
        AdminServices $adminServices,
        int $id
    ): Response {
        $user = $security->getUser();
        $is_admin = false;
        if ($user->getIsAdmin()) {
            $is_admin = true;
        }

        try {
            $adminServices->closeFeedback($user, $id, $is_admin);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()]);
        }
        return $this->jsonSuccess(['result' => 'Обращение успешно закрыто']);
    }
}
