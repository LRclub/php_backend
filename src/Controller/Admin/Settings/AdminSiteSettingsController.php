<?php

namespace App\Controller\Admin\Settings;

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
use App\Services\Settings\SettingsServices;
use Symfony\Component\Security\Core\Exception\LogicException;

class AdminSiteSettingsController extends BaseApiController
{
    /**
     * @Route("/admin/settings", name="admin_site_settings_index", methods={"GET"})
     */
    public function index(SiteSettingsRepository $siteSettingsRepository): Response
    {
        return $this->render('/pages/admin/site_settings/index.html.twig', [
            'site_settings' => $siteSettingsRepository->findBy([], ['id' => 'desc']),
        ]);
    }

    /**
     * @Route("/admin/settings/{id}", name="admin_site_settings_show", methods={"GET"})
     */
    public function show(SiteSettings $siteSetting): Response
    {
        return $this->render('/pages/admin/site_settings/show.html.twig', [
            'site_setting' => $siteSetting,
        ]);
    }
}
