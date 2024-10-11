<?php

namespace App\Controller\Api\Journal;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Annotations as OA;
use App\Services\Journal\JournalServices;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Form\Journal\JournalWorkbookType;

class JournalWorkbookController extends BaseApiController
{
    /**
     * Рабочая тетрадь. Сохранение результата
     *
     * @Route("/api/journal/workbook", name="api_journal_workbook_update", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="goal", type="string", description="Цель недели", example="Выполнить все"),
     *       @OA\Property(property="result", type="string", description="Результат недели", example="Все сделал"),
     *       @OA\Property(property="type", type="string", description="week/month/year", example="week"),
     *       @OA\Property(property="date", type="date", description="Дата", example="2023-05-01"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Запись успешно сохранена")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="journal workbook")
     * @Security(name="Bearer")
     */
    public function journalWorkbookUpdateAction(
        CoreSecurity $security,
        Request $request,
        JournalServices $journalServices
    ): Response {
        $user = $security->getUser();

        $workbook = [
            'goal' => trim($this->getJson($request, 'goal')),
            'result' => trim($this->getJson($request, 'result')),
            'type' => trim($this->getJson($request, 'type')),
            'date' => $this->getJson($request, 'date'),
        ];

        $form = $this->createFormByArray(JournalWorkbookType::class, $workbook);
        if ($form->isValid()) {
            $result = $journalServices->saveWorkbook($user, $form);

            return $this->jsonSuccess(['result' => $result->getWorkbookArray()]);
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }
    }

    /**
     * Рабочая тетрадь. Значения за год
     *
     * @Route(path="/api/journal/workbook/year", name="api_journal_workbook_year", methods={"GET"})
     *
     * @OA\Parameter(
     *              in="query", name="date",
     *               schema={"type"="string", "example"="2023"},
     *              description="Год YY"
     *              )
     *
     * @OA\Response(response=200, description="Значения успешно получены")
     * @OA\Response(response=401, description="Необходима авторизация")
     *
     * @OA\Tag(name="journal workbook")
     * @Security(name="Bearer")
     */
    public function journalWorkbookYearAction(
        CoreSecurity $security,
        Request $request,
        JournalServices $journalServices
    ) {
        $user = $security->getUser();
        $date = $request->query->get('date');

        $result = $journalServices->getWorkbookYear($user, $date);

        return $this->jsonSuccess(['result' => $result]);
    }

    /**
     * Рабочая тетрадь. Значения за месяц
     *
     * @Route(path="/api/journal/workbook/month", name="api_journal_workbook_month", methods={"GET"})
     *
     * @OA\Parameter(
     *              in="query", name="date",
     *               schema={"type"="string", "example"="2023-05"},
     *              description="Год и месяц YY-MM"
     *              )
     *
     * @OA\Response(response=200, description="Значения успешно получены")
     * @OA\Response(response=401, description="Необходима авторизация")
     *
     * @OA\Tag(name="journal workbook")
     * @Security(name="Bearer")
     */
    public function journalWorkbookMonthAction(
        CoreSecurity $security,
        Request $request,
        JournalServices $journalServices
    ) {
        $user = $security->getUser();
        $date = $request->query->get('date');

        $result = $journalServices->getWorkbookMonth($user, $date);

        return $this->jsonSuccess(['result' => $result]);
    }

    /**
     * Рабочая тетрадь. Значения за неделю
     *
     * @Route(path="/api/journal/workbook/week", name="api_journal_workbook_week", methods={"GET"})
     *
     * @OA\Parameter(
     *              in="query", name="date_from",
     *               schema={"type"="string", "example"="2023-05-01"},
     *              description="Дата начала недели"
     *              ),
     * @OA\Parameter(
     *              in="query", name="date_to",
     *              schema={"type"="string", "example"="2023-05-07"},
     *              description="Дата окончания недели"
     *              )
     *
     * @OA\Response(response=200, description="Значения успешно получены")
     * @OA\Response(response=400, description="Ошибка валидации")
     * @OA\Response(response=401, description="Необходима авторизация")
     *
     * @OA\Tag(name="journal workbook")
     * @Security(name="Bearer")
     */
    public function journalWorkbookWeekAction(
        CoreSecurity $security,
        Request $request,
        JournalServices $journalServices
    ) {
        $user = $security->getUser();

        $date = [
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to')
        ];

        try {
            $result = $journalServices->getWorkbookWeek($user, $date);
        } catch (LogicException $e) {
            return $this->jsonError(['date' => $e->getMessage()], 400);
        }
        return $this->jsonSuccess(['result' => $result]);
    }
}
