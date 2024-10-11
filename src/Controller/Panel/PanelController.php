<?php

namespace App\Controller\Panel;

use App\Services\PanelServices;
use App\Services\User\UserServices;
use App\Controller\Base\BaseApiController;
use App\Services\Marketing\PromocodeServices;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PanelController extends BaseApiController
{
    /**
     * Профиль пользователя
     *
     * @Route("/panel/settings", name="panel_user", methods={"GET"})
     */
    public function panelSettingsAction(): Response
    {
        return $this->render('/pages/panel/panel.html.twig', [
            'title' => 'Мой профиль'
        ]);
    }

    /**
     * Главная страница панели
     *
     * @Route("/panel/", name="panel_index", methods={"GET"})
     */
    public function indexAction(
        CoreSecurity $security,
        PanelServices $panelServices
    ): Response {
        $user = $security->getUser();

        if ($user) {
            $template_data = array_merge(['title' => 'Главная'], $panelServices->getData($user));

            return $this->render('/pages/home.twig', $template_data);
        } else {
            return $this->redirect('/');
        }
    }

    /**
     * Профиль пользователя
     *
     * @Route("/panel/profile/{id}", requirements={"id"="\d+"}, name="panel_user_profile", methods={"GET"})
     *
     *
     */
    public function profileAction(
        int $id,
        UserServices $userServices,
        CoreSecurity $security
    ): Response {
        $user_info = $userServices->getUserProfileInfo($id);
        if (!empty($user)) {
            throw new NotFoundHttpException();
        }

        return $this->render('/pages/panel/profile.html.twig', [
            'result' => $user_info,
            'title' => 'Профиль пользователя'
        ]);
    }

    /**
     * Заполнение общей информации после регистрации
     *
     * @Route("/panel/init", name="panel_init", methods={"GET"})
     */
    public function panelInitAction(CoreSecurity $security, PromocodeServices $promocodeServices): Response
    {
        $user = $security->getUser();
        if (!$promocodeServices->isPromocodeAvailable($user)) {
            return  $this->redirect('/panel');
        }

        return $this->render('/pages/panel/init.html.twig', [
            'title' => 'Заполнение информации профиля'
        ]);
    }
}
