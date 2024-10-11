<?php

namespace App\Controller\Tracker;

use App\Controller\Base\BaseApiController;
use App\Services\Marketing\PromocodeServices;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Services\Admin\AdminTrackerTemplateServices;

class TrackerController extends BaseApiController
{
    /**
     * Трекер
     *
     * @Route("/panel/tracker", name="panel_tracker", methods={"GET"})
     */
    public function trackerAction(AdminTrackerTemplateServices $adminTrackerTemplateServices): Response
    {
        $tasks_template = $adminTrackerTemplateServices->getDefaultTrackerTasks();
        return $this->render('/pages/tracker/list.html.twig', [
            'title' => 'Трекер',
            'tasks_template' => $tasks_template
        ]);
    }
}
