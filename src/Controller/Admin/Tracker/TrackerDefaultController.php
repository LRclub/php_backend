<?php

namespace App\Controller\Admin\Tracker;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Base\BaseApiController;
use App\Services\Admin\AdminTrackerTemplateServices;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TrackerDefaultController extends BaseApiController
{
    /**
     * @Route("/admin/tracker", name="admin_tracker", methods={"GET"})
     */
    public function indexAction(AdminTrackerTemplateServices $adminTrackerTemplateServices): Response
    {
        $tasks = $adminTrackerTemplateServices->getDefaultTrackerTasks();

        return $this->render('/pages/admin/tracker/list.html.twig', [
            'tasks' => $tasks
        ]);
    }

    /**
     * Создание задачи
     *
     * @Route("/admin/tracker/create", name="admin_tracker_create", methods={"GET"})
     */
    public function createAction(
        AdminTrackerTemplateServices $adminTrackerTemplateServices
    ): Response {
        return $this->render('/pages/admin/tracker/create.html.twig', []);
    }

    /**
     * Редактирование задачи
     *
     * @Route("/admin/tracker/{id}", requirements={"id"="\d+"}, name="admin_tracker_update", methods={"GET"})
     */
    public function updateAction(
        AdminTrackerTemplateServices $adminTrackerTemplateServices,
        int $id
    ): Response {
        $task = $adminTrackerTemplateServices->getDefaultTrackerTaskById($id);
        if (empty($task)) {
            throw new NotFoundHttpException();
        }
        return $this->render('/pages/admin/tracker/update.html.twig', [
            'task' => $task
        ]);
    }
}
