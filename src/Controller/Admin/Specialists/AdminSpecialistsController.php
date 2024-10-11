<?php

namespace App\Controller\Admin\Specialists;

use App\Entity\SiteSettings;
use App\Form\SiteSettingsType;
use App\Repository\SiteSettingsRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\Admin\AdminSpecialistsServices;

class AdminSpecialistsController extends BaseApiController
{
    /**
     * Список специалистов
     *
     * @Route("/admin/specialists", name="admin_specialists", methods={"GET"})
     *
     */
    public function listAction(
        Request $request,
        AdminSpecialistsServices $adminSpecialistsServices
    ): Response {
        // Сортировка
        $search = trim($request->query->get('search'));
        $order_by['sort_param'] = mb_strtolower($request->query->get('sort'));
        $order = mb_strtolower($request->query->get('order'));
        $order_by['sort_type'] = ($order == self::SORT_ASC) ? self::SORT_ASC : self::SORT_DESC;
        $page = $this->getIntOrNull($request->query->get('page')) ?? 1;

        $result = $adminSpecialistsServices->getSpecialistsList($page, $order_by, $search);

        return $this->render('/pages/admin/specialist/list.html.twig', [
            'search' => $search,
            'specialists' => $result['specialists'],
            'specialists_count' => $result['specialists_count'],
            'current_page' => $page,
            'pages_count' => round($result['specialists_count'] / $adminSpecialistsServices::PAGE_OFFSET)
        ]);
    }

    /**
     * Создание специалиста
     *
     * @Route("/admin/specialist/create", name="admin_specialist_create", methods={"GET"})
     */
    public function createAction(): Response
    {
        return $this->render('/pages/admin/specialist/create.html.twig', []);
    }

    /**
     * Создание специалиста
     *
     * @Route("/admin/specialist/{id}", requirements={"id"="\d+"}, name="admin_specialist_view", methods={"GET"})
     */
    public function viewAction(
        AdminSpecialistsServices $adminSpecialistsServices,
        Request $request,
        $id
    ): Response {
        $specialist = $adminSpecialistsServices->getSpecialistById($id);
        if (!$specialist) {
            throw new NotFoundHttpException();
        }

        return $this->render('/pages/admin/specialist/view.html.twig', [
            'specialist' => $specialist
        ]);
    }

    /**
     * Cписок заявкок на консультацию
     *
     * @Route("/admin/specialists/requests", name="admin_specialist_requests", methods={"GET"})
     */
    public function requestsAction(
        AdminSpecialistsServices $adminSpecialistsServices,
        Request $request
    ) {
        $page = $this->getIntOrNull($request->query->get('page')) ?? 1;

        $result = $adminSpecialistsServices->getSpecialistsRequests($page);

        return $this->render('/pages/admin/specialist/requests.html.twig', [
            'title' => "Заявки специалистов",
            'requests' => $result['requests'],
            'pages' => $result['pages_count'],
            'page' => $page
        ]);
    }
}
