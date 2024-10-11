<?php

namespace App\Controller;

use App\Controller\Base\BaseApiController;
use App\Repository\UserRepository;
use App\Repository\PromocodesRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\Specialist\RequestsServices;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use OpenApi\Annotations as OA;
use App\Services\Materials\MaterialsServices;

class IndexController extends BaseApiController
{
    /**
     * Главная страница
     *
     * @Route("/", name="index", methods={"GET"})
     */
    public function indexAction(
        CoreSecurity $security,
        MaterialsServices $materialsServices
    ): Response {
        return $this->render('/pages/landing.twig');
    }
}
