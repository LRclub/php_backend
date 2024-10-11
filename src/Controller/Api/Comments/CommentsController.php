<?php

namespace App\Controller\Api\Comments;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\Exception\LogicException;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Annotations as OA;
use App\Form\Comments\CommentCreateType;
use App\Form\Comments\CommentUpdateType;
use App\Services\Comments\CommentsServices;
use App\Services\QueueServices;

class CommentsController extends BaseApiController
{
    /**
     * Создание комментария
     *
     * @Route("/api/comment", name="api_comment", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="comments_collector_id", type="integer", description="id", example="1"),
     *       @OA\Property(property="reply_id", type="integer", description="id ответа", example="1"),
     *       @OA\Property(property="text", type="text", description="Текст комментария", example="Классный курс!")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Комментарий успешно создан")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="comments")
     * @Security(name="Bearer")
     */
    public function createComment(
        CoreSecurity $security,
        Request $request,
        CommentsServices $commentsServices,
        QueueServices $queueServices
    ): Response {
        $user = $security->getUser();
        $reply_id = (int)$this->getJson($request, 'reply_id');
        $comment['reply'] = !empty($reply_id) ? intval($reply_id) : null;
        $comment['comments_collector'] = (int)$this->getJson($request, 'comments_collector_id');
        $comment['text'] = (string)$this->getJson($request, 'text');

        $form = $this->createFormByArray(CommentCreateType::class, $comment);
        $result = [];
        if ($form->isValid()) {
            try {
                $comment = $commentsServices->createComment($form, $user);
                $queueServices->sendComments('send', $comment->getId());
                if ($comment->getLikesCollector()->getMaterial()) {
                    $queueServices->sendEvents('comments', $comment->getLikesCollector()->getMaterial()->getId());
                }
                $result = $commentsServices->getMessageForSocket('send', $comment);
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
     * Редактирование комментария
     *
     * @Route("/api/comment", name="api_comment_update", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="comment_id", type="integer", description="Id комментария", example="1"),
     *       @OA\Property(property="text", type="text", description="Текст комментария", example="Классный курс!")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Комментарий успешно создан")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="comments")
     * @Security(name="Bearer")
     */
    public function updateComment(
        Request $request,
        CommentsServices $commentsServices,
        QueueServices $queueServices
    ) {
        $comment['comment_id'] = $this->getIntOrNull($this->getJson($request, 'comment_id'));
        $comment['text'] = (string)$this->getJson($request, 'text');

        $form = $this->createFormByArray(CommentUpdateType::class, $comment);
        $result = [];
        if ($form->isValid()) {
            try {
                $comment = $commentsServices->updateComment($form);
                $queueServices->sendComments('edit', $comment->getId());
                $result = $commentsServices->getMessageForSocket('edit', $comment);
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
     * Удалить комментарий
     *
     * @Route("/api/comment/{id}", requirements={"id"="\d+"}, name="api_comment_delete", methods={"DELETE"})
     *
     * @OA\Parameter(name="id", in="path", description="ID комментария",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Комментарий успешно удален")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="comments")
     * @OA\Tag(name="admin comments")
     * @Security(name="Bearer")
     */
    public function deleteComment(
        CoreSecurity $security,
        Request $request,
        CommentsServices $commentsServices,
        QueueServices $queueServices,
        int $id
    ): Response {
        $user = $security->getUser();

        try {
            $comment = $commentsServices->deleteComment($id, $user);
            $queueServices->sendComments('delete', $comment->getId());
            $commentsServices->getMessageForSocket('delete', $comment);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()]);
        }

        return $this->jsonSuccess(['result' => "Комментарий успешно удален"]);
    }

    /**
     * Восстановить комментарий
     *
     * @Route("/api/admin/comment/return/{id}", requirements={"id"="\d+"},
     *  name="api_admin_comment_return",
     *  methods={"PATCH"}
     * )
     *
     * @OA\Parameter(name="id", in="path", description="ID комментария",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Комментарий успешно восстановлен")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="comments")
     * @OA\Tag(name="admin comments")
     * @Security(name="Bearer")
     */
    public function returnComment(
        CoreSecurity $security,
        Request $request,
        CommentsServices $commentsServices,
        QueueServices $queueServices,
        int $id
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsModerator() && !$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        try {
            $comment = $commentsServices->adminReturnComment($id, $user);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => $e->getMessage()]);
        }

        return $this->jsonSuccess(['result' => "Комментарий успешно восстановлен"]);
    }

    /**
     * Поставить/убрать лайк
     *
     * @Route("/api/like", name="api_like", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="likes_collector_id", type="integer", description="Id коллектора", example="1"),
     *       @OA\Property(property="like_status", type="boolean", description="status", example="true")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Успешно")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="comments")
     * @Security(name="Bearer")
     */
    public function setCommentLike(
        CoreSecurity $security,
        Request $request,
        CommentsServices $commentsServices
    ) {
        $user = $security->getUser();
        $likes_collector_id = (int)$this->getJson($request, 'likes_collector_id');
        $likes_collector_id = !empty($likes_collector_id) ? intval($likes_collector_id) : 0;
        $like_status = (bool)$this->getJson($request, 'like_status') ?? false;
        $status = false;

        try {
            $status = $commentsServices->likeComment($likes_collector_id, $like_status, $user);
        } catch (LogicException $e) {
            return $this->jsonError(['likes_collector_id' => $e->getMessage()]);
        }

        return $this->jsonSuccess(['like' => $status]);
    }

    /**
     * Получить список комментариев
     *
     * @Route(path="/api/comments", name="api_comments", methods={"GET"})
     *
     * @OA\Get(path="/api/comments?", operationId="getComments"),
     *
     * @OA\Parameter(
     *              in="query", name="comment_id",
     *               schema={"type"="integer", "example"=1},
     *              description="ID последнего комментария"
     *              ),
     * @OA\Parameter(
     *              in="query", name="collector_id",
     *              schema={"type"="integer", "example"=1},
     *              description="ID коллектора комментария"
     *              )
     *
     * @OA\Response(response=200, description="Комментарии получены")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=401, description="Необходима авторизация")
     *
     * @OA\Tag(name="comments")
     * @Security(name="Bearer")
     */
    public function getComments(
        Request $request,
        CommentsServices $commentsServices
    ) {
        $comment_id = $this->getIntOrNull($request->query->get('comment_id'));
        $collector_id = (int)$request->query->get('collector_id');
        if (empty($collector_id)) {
            return $this->jsonError(['collector_id' => 'Нужно указать collector ID']);
        }

        $result = $commentsServices->getComments($comment_id, $collector_id);

        return $this->jsonSuccess($result);
    }

    /**
     * Получить список ответов на комментарий
     *
     * @Route(path="/api/comments/reply", name="api_comments_reply", methods={"GET"})
     *
     * @OA\Get(path="/api/comments/reply?", operationId="getReply"),
     *
     * @OA\Parameter(
     *              in="query", name="last_comment_id",
     *               schema={"type"="integer", "example"=1},
     *              description="ID последнего сообщения подгрузки"
     *              ),
     * @OA\Parameter(
     *              in="query", name="collector_id",
     *              schema={"type"="integer", "example"=1},
     *              description="ID коллектора комментария"
     *              ),
     * @OA\Parameter(
     *              in="query", name="comment_id",
     *              schema={"type"="integer", "example"=1},
     *              description="ID комментария"
     *              )
     *
     * @OA\Response(response=200, description="Комментарии получены")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=401, description="Необходима авторизация")
     *
     * @OA\Tag(name="comments")
     * @Security(name="Bearer")
     */
    public function getReply(
        Request $request,
        CommentsServices $commentsServices
    ) {
        $last_comment_id = $this->getIntOrNull($request->query->get('last_comment_id'));
        $collector_id = (int)$request->query->get('collector_id');
        $comment_id = $this->getIntOrNull($request->query->get('comment_id'));
        if (empty($collector_id)) {
            return $this->jsonError(['collector_id' => 'Нужно указать collector ID']);
        }

        $result = $commentsServices->getReply($last_comment_id, $collector_id, $comment_id);

        return $this->jsonSuccess($result);
    }
}
