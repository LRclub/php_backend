<?php

namespace App\Controller\Calendar;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CalendarController extends BaseApiController
{
    /**
     * Страница календаря
     *
     * @Route("/panel/calendar", name="panel_calendar", methods={"GET"})
     */
    public function calendarAction(CoreSecurity $security): Response
    {
        return $this->render('/pages/calendar/index.html.twig', []);
    }

    /**
     * Страница задач
     *
     * @Route("/panel/calendar/tasks", name="panel_tasks", methods={"GET"})
     */
    public function tasksAction(CoreSecurity $security): Response
    {
        return $this->render('/pages/calendar/tasks.html.twig', []);
    }
}
