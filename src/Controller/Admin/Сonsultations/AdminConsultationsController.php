<?php

namespace App\Controller\Admin\Сonsultations;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\Admin\AdminSpecialistsCategoriesServices;

class AdminConsultationsController extends BaseApiController
{
    /**
     * Просмотр консультаций
     *
     * @Route("/admin/consultations", name="admin_consultations_list", methods={"GET"})
     */
    public function consultationsAction(
        CoreSecurity $security,
        AdminSpecialistsCategoriesServices $adminSpecialistsCategoriesServices,
        Request $request
    ): Response {

        $search = trim($request->query->get('search'));
        $result = $adminSpecialistsCategoriesServices->getCategoriesAdmin($search);

        return $this->render('/pages/admin/consultations/list.html.twig', [
            'result' => $result,
            'search' => $search,
        ]);
    }

    /**
     * Создание консультации
     *
     * @Route("/admin/consultations/create", name="admin_consultations_create", methods={"GET"})
     */
    public function consultationsCreateAction(
        CoreSecurity $security,
        AdminSpecialistsCategoriesServices $adminSpecialistsCategoriesServices
    ): Response {
        return $this->render('/pages/admin/consultations/create.html.twig', []);
    }

    /**
     * Просмотр консультации
     *
     * @Route("/admin/consultations/{id}", requirements={"id"="\d+"}, name="admin_consultations_view", methods={"GET"})
     */
    public function consultationsViewAction(
        CoreSecurity $security,
        AdminSpecialistsCategoriesServices $adminSpecialistsCategoriesServices,
        $id
    ): Response {

        $result = $adminSpecialistsCategoriesServices->getAdminCategoryById($id)[0];

        if (!$result) {
            throw new NotFoundHttpException();
        }

        return $this->render('/pages/admin/consultations/view.html.twig', [
            'result' => $result
        ]);
    }
}
