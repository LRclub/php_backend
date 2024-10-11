<?php

namespace App\Controller\Api\Notice;

use App\Controller\Base\BaseApiController;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\Notice\NoticeServices;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use App\Form\Notice\NoticeCreateType;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\Chat\ChatServices;

class NoticeController extends BaseApiController
{
    /**
     * Сделать уведомление прочитанным
     *
     * @Route("/api/notice/{id}", requirements={"id"="-?\d+"}, name="api_notice_delete", methods={"DELETE"})
     *
     * @OA\Parameter(name="id", in="path", description="ID уведомления",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Уведомление прочитано")
     * @OA\Response(response=401, description="Необходима авторизация")
     * @OA\Response(response=404, description="Уведомление не найдено")
     *
     * @OA\Tag(name="notice")
     * @Security(name="Bearer")
     */
    public function setIsReadNoticeAction(
        CoreSecurity $security,
        Request $request,
        NoticeServices $noticeServices,
        ChatServices $chatServices,
        int $id
    ): Response {
        $user = $security->getUser();

        if (empty($id)) {
            return $this->jsonError(['notice_id' => "Нужно указать id"]);
        }

        // Notice из базы (если число положительное или 0)
        if ($id >= 0) {
            $notice = $noticeServices->getUnreadNoticeById($id);
            if (empty($notice) || $notice->getIsRead()) {
                return $this->jsonError(['id' => "Уведомление не найдено"]);
            }

            if ($notice->getUser()->getId() != $user->getId()) {
                return $this->jsonError(['id' => "Это уведомление Вам не принадлежит"]);
            }

            $noticeServices->setIsReadNotice($notice);
        }

        // Делаем прочитанными искусственно созданные сообщения в чатах
        if ($id < 0) {
            $chatServices->setIsReadChatMessages($user, abs($id));
        }

        return $this->jsonSuccess();
    }

    /**
     * Сделать все уведомления прочитанным
     *
     * @Route("/api/notice/all",  name="api_notice_delete_all", methods={"DELETE"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json"
     *   )
     * )
     *
     * @OA\Response(response=200, description="Уведомления прочитаны")
     * @OA\Response(response=401, description="Необходима авторизация")
     *
     * @OA\Tag(name="notice")
     * @Security(name="Bearer")
     */
    public function setIsReadNoticesAction(
        CoreSecurity $security,
        NoticeServices $noticeServices,
        ChatServices $chatServices
    ): Response {
        $user = $security->getUser();

        $noticeServices->setIsReadNoticesAll($user);
        $chatServices->setIsReadChatMessagesNotice($user);

        return $this->jsonSuccess();
    }

    /**
     * Создать уведомление
     *
     * @Route("/api/notice", name="api_notice_create", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="type", type="string", description="success/error/warning/info", example="info"),
     *       @OA\Property(property="category", type="string", description="category of notification", example="comments"),
     *       @OA\Property(
     *                   property="message",
     *                   type="string", description="Текст уведомления",
     *                   example="Вам ответили в комментариях"
     *      ),
     *      @OA\Property(property="data", type="json", description="Дополнительные данные объекта", example={"link": "https://lrclub.sunadv.ru/panel/material/113"}),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Уведомление создано")
     * @OA\Response(response=401, description="Необходима авторизация")
     * @OA\Response(response=404, description="Не удалось создать уведомление")
     *
     * @OA\Tag(name="notice")
     * @Security(name="Bearer")
     */
    public function createNoticeAction(
        CoreSecurity $security,
        Request $request,
        NoticeServices $noticeServices
    ): Response {
        $user = $security->getUser();
        $data['type'] = mb_strtolower((string)$this->getJson($request, 'type'));
        $data['category'] = mb_strtolower((string)$this->getJson($request, 'category'));
        $data['message'] = (string)$this->getJson($request, 'message');
        $data['data'] = (array)$this->getJson($request, 'data');

        $form = $this->createFormByArray(NoticeCreateType::class, $data);
        if ($form->isValid()) {
            try {
                $noticeServices->createNotice($user, $form);
            } catch (LogicException $e) {
                return $this->jsonError(['#' => $e->getMessage()]);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess();
    }
}
