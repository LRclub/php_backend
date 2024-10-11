<?php

namespace App\Controller\Api\Admin\Feedback;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\User\FeedbackServices;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Repository\UserRepository;
use App\Services\Admin\AdminServices;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Form\Feedback\FeedbackCreateType;

class AdminFeedbackController extends BaseApiController
{
    /**
     * Создание заявки обратной связи админом
     *
     * @Route("/api/admin/feedback", name="api_admin_create_feedback", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="user_id", type="integer", description="id пользователя", example="1"),
     *       @OA\Property(property="title",
     *                      type="string",
     *                      description="Заголовок", example="Какие документы требуются для модерации?"
     *                  ),
     *       @OA\Property(property="message",
     *                      type="text",
     *                      description="Сообщение",
     *                      example="Предоставил паспорт, но модерацию не прошел"
     *                  ),
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
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin feedback")
     * @Security(name="Bearer")
     */
    public function createUserFeedbackAction(
        CoreSecurity $security,
        Request $request,
        FeedbackServices $feedbackServices,
        UserRepository $userRepository
    ): Response {
        $admin = $security->getUser();
        if (!$admin->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $feedback['title'] = (string)$this->getJson($request, 'title');
        $feedback['message'] = (string)$this->getJson($request, 'message');
        $feedback['files'] = (array)$this->getJson($request, 'files');
        $feedback['user_id'] = (int)$this->getJson($request, 'user_id');

        $user = $userRepository->find($feedback['user_id']);
        if (!$user) {
            return $this->jsonError(['user_id' => "Пользователь не найден"], 400);
        }

        if ($admin->getId() == $user->getId()) {
            return $this->jsonError(['user_id' => "Нельзя создать заявку на себя"], 400);
        }

        $form = $this->createFormByArray(FeedbackCreateType::class, $feedback);
        if ($form->isValid()) {
            try {
                $feedbackServices->saveFeedback($user, $form, true);
            } catch (LogicException $e) {
                return $this->jsonError(['#' => $e->getMessage()]);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Обращение успешно создано"]);
    }

    /**
     * Поиск пользователя для создания обратной связи
     *
     * @Route("/api/admin/search", name="api_admin_search", methods={"GET"})
     *
     * @OA\Parameter(in="query", name="text",
     *               schema={"type"="string", "example"="Иван Иванов"},
     *               description="Текст поиска"
     *              )
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=401, description="Необходима авторизация")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin feedback")
     * @Security(name="Bearer")
     */
    public function adminUserSearch(
        CoreSecurity $security,
        Request $request,
        AdminServices $adminServices
    ) {
        $admin = $security->getUser();

        if (!$admin->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $search_text = trim((string)$request->query->get('text'));
        $result = $adminServices->userSearch($search_text);

        return $this->jsonSuccess(['result' => $result]);
    }
}
