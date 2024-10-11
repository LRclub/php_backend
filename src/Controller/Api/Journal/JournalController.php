<?php

namespace App\Controller\Api\Journal;

use App\Controller\Base\BaseApiController;
use App\Form\Journal\JournalGratitudeType;
use App\Form\Journal\JournalReportType;
use App\Form\User\UpdateUserType;
use App\Services\Messages\MessagesServices;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Services\Admin\AdminServices;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Annotations as OA;
use App\Services\Journal\JournalServices;
use Symfony\Component\Security\Core\Exception\LogicException;

class JournalController extends BaseApiController
{
    /**
     * Дневник благодарности (создать/редактировать)
     *
     * @Route("/api/journal/gratitude", name="api_journal_gratitude_update", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="answer", type="json", description="Ответ",
     *     example={"Раз два три", "четыре пять", "шесть семь восемь"}),
     *       @OA\Property(property="date", type="date", description="Дата", example="2023-05-04"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Ответ успешно сохранен")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="journal")
     * @Security(name="Bearer")
     */
    public function journalGratitudeUpdateAction(
        CoreSecurity $security,
        Request $request,
        JournalServices $journalServices
    ): Response {
        $user = $security->getUser();

        $answer = $this->getJson($request, 'answer');
        $date = (string)$this->getJson($request, 'date');

        $form = $this->createFormByArray(JournalGratitudeType::class, [
            'answer' => $answer,
            'date' => $date
        ]);

        try {
            if ($form->isValid()) {
                $result = $journalServices->saveAnswer(
                    $user,
                    $answer,
                    $date,
                    $journalServices::JOURNAL_ANSWER_GRATITUDE
                );

                return $this->jsonSuccess(['result' => $result->getArrayGratitude()]);
            } else {
                return $this->formValidationError($form);
            }
        } catch (LogicException $e) {
            return $this->jsonError(["#" => $e->getMessage()], 400);
        }
    }

    /**
     * API дневник благодарности
     *
     * @Route(path="/api/journal/gratitude", name="api_journal_gratitude", methods={"GET"})
     *
     * @OA\Parameter(
     *              in="query", name="date",
     *               schema={"type"="string", "example"="2023-06-07"},
     *              description="Год, месяц, день"
     *              )
     *
     * @OA\Response(response=200, description="Ответ успешно получен")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=401, description="Необходима авторизация")
     *
     * @OA\Tag(name="journal")
     * @Security(name="Bearer")
     */
    public function getJournalGratitudeAction(
        CoreSecurity $security,
        Request $request,
        JournalServices $journalServices
    ) {
        $user = $security->getUser();

        $date = strval($request->query->get('date'));
        try {
            $answer = $journalServices->getAnswer($user, $date, $journalServices::JOURNAL_ANSWER_GRATITUDE);
        } catch (LogicException $e) {
            return $this->jsonError(["#" => $e->getMessage()], 400);
        }

        return $this->jsonSuccess(['result' => $answer]);
    }

    /**
     * API отчет дня (Дневник рефлексии) (создать/редактировать)
     *
     * @Route("/api/journal/report", name="api_journal_report_update", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="answer", type="json", description="Ответ",
     *     example={"Раз два три", "четыре пять", "шесть семь восемь"}),
     *       @OA\Property(property="date", type="date", description="Дата", example="2023-05-04"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Ответ успешно сохранен")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="journal")
     * @Security(name="Bearer")
     */
    public function journalReportUpdateAction(
        CoreSecurity $security,
        Request $request,
        JournalServices $journalServices
    ): Response {
        $user = $security->getUser();

        $answer = $this->getJson($request, 'answer');
        $date = (string)$this->getJson($request, 'date');


        $form = $this->createFormByArray(JournalReportType::class, [
            'answer' => $answer,
            'date' => $date
        ]);

        try {
            if ($form->isValid()) {
                $result = $journalServices->saveAnswer($user, $answer, $date, $journalServices::JOURNAL_ANSWER_REPORT);

                return $this->jsonSuccess(['result' => $result->getArrayReport()]);
            } else {
                return $this->formValidationError($form);
            }
        } catch (LogicException $e) {
            return $this->jsonError(["#" => $e->getMessage()], 400);
        }
    }

    /**
     * API отчет дня (Дневник рефлексии)
     *
     * @Route(path="/api/journal/report", name="api_journal_report", methods={"GET"})
     *
     * @OA\Parameter(
     *              in="query", name="date",
     *               schema={"type"="string", "example"="2023-06-07"},
     *              description="Год, месяц, день"
     *              )
     *
     * @OA\Response(response=200, description="Ответ успешно получен")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=401, description="Необходима авторизация")
     *
     * @OA\Tag(name="journal")
     * @Security(name="Bearer")
     */
    public function getJournalReportAction(
        CoreSecurity $security,
        Request $request,
        JournalServices $journalServices
    ) {
        $user = $security->getUser();

        $date = strval($request->query->get('date'));
        try {
            $answer = $journalServices->getAnswer($user, $date, $journalServices::JOURNAL_ANSWER_REPORT);
        } catch (LogicException $e) {
            return $this->jsonError(["#" => $e->getMessage()], 400);
        }

        return $this->jsonSuccess(['result' => $answer]);
    }
}
