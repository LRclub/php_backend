<?php

namespace App\Controller\Api\Calendar;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Annotations as OA;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\Calendar\CalendarServices;

class CalendarController extends BaseApiController
{
    /**
     * Получить данные для календаря
     *
     * @Route("/api/calendar", name="api_calendar", methods={"GET"})
     *
     * @OA\Get(path="/api/calendar?", operationId="getCalendarAction"),
     *
     * @OA\Parameter(
     *              in="query", name="date",
     *               schema={"type"="string", "example"="2023-05"},
     *              description="Год и месяц "
     *              )
     *
     * @OA\Response(response=200, description="Информация предоставлена")
     * @OA\Response(response=400, description="Ошибка")
     *
     * @OA\Tag(name="calendar")
     * @Security(name="Bearer")
     */
    public function getCalendarAction(
        CoreSecurity $security,
        Request $request,
        CalendarServices $calendarServices
    ): Response {
        $user = $security->getUser();
        $date = $request->query->get('date');

        try {
            $result = $calendarServices->getCalendarList($user, $date);
        } catch (LogicException $e) {
            return $this->jsonError(['date' => $e->getMessage()]);
        }
        return $this->jsonSuccess(['result' => $result]);
    }
}
