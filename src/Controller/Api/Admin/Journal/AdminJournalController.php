<?php

namespace App\Controller\Api\Admin\Journal;

use App\Form\Journal\JournalQuestionAddType;
use App\Services\Seo\SeoServices;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Controller\Base\BaseApiController;
use OpenApi\Annotations as OA;
use App\Form\Journal\JournalQuestionEditType;
use App\Services\Marketing\PromocodeServices;
use App\Services\Journal\JournalServices;

class AdminJournalController extends BaseApiController
{
    /**
     * Api редактирования вопросов для дневника
     *
     * @Route("/api/admin/journal", name="api_admin_journal_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="question_id", type="integer", description="ID вопроса", example="1"),
     *       @OA\Property(property="sort", type="integer", description="Очередность", example="1"),
     *       @OA\Property(property="question_text", type="string", description="Вопрос", example="Как прошел день?"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Вопрос успешно изменен")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin journal")
     * @Security(name="Bearer")
     */
    public function adminJournalQuestionEditAction(
        Request $request,
        JournalServices $journalServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $journal = [
            'question' => $this->getIntOrNull($this->getJson($request, 'question_id')),
            'sort' => $this->getIntOrNull($this->getJson($request, 'sort')),
            'question_text' => (string)$this->getJson($request, 'question_text')
        ];

        $form = $this->createFormByArray(JournalQuestionEditType::class, $journal);
        if ($form->isValid()) {
            $journalServices->adminEditQuestion($form);
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Вопрос успешно изменен"]);
    }


    /**
     * Api удаления записи дневников (только дневники рефлексии / рабочей тетради)
     *
     * @Route("/api/admin/journal/{id}", requirements={"id"="\d+"}, name="api_admin_journal_delete", methods={"DELETE"})
     *
     * @OA\Parameter(name="id", in="path", description="ID вопроса",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Вопрос успешно удален")
     * @OA\Response(response=401, description="Ошибка удаления")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin journal")
     * @Security(name="Bearer")
     */
    public function adminDeleteQuestionAction(
        JournalServices $journalServices,
        CoreSecurity $security,
        int $id
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        if (empty($id)) {
            return $this->jsonError(["id" => "Нужно указать id"], 400);
        }

        try {
            $journalServices->adminDeleteQuestion($id);
        } catch (LogicException $e) {
            return $this->jsonError(["id" => $e->getMessage()]);
        }

        return $this->jsonSuccess(['result' => "Вопрос успешно удален"]);
    }


    /**
     * Api добавление вопросов для дневника рефлексии (рабочей тетради)
     *
     * @Route("/api/admin/journal", name="api_admin_journal_add", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="sort", type="integer", description="Очередность", example="1"),
     *       @OA\Property(property="text", type="string", description="Вопрос", example="Как прошел день?"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Вопрос успешно изменен")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin journal")
     * @Security(name="Bearer")
     */
    public function adminJournalQuestionAddAction(
        Request $request,
        JournalServices $journalServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $journal = [
            'sort' => $this->getIntOrNull($this->getJson($request, 'sort')),
            'text' => (string)$this->getJson($request, 'text')
        ];

        $form = $this->createFormByArray(JournalQuestionAddType::class, $journal);

        if ($form->isValid()) {
            $question = $journalServices->adminAddQuestion($form);
        } else {
            $errors = $this->getErrorMessages($form);

            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => $question->getArrayData()]);
    }
}
