<?php

namespace App\Controller\Api\Admin\Settings;

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
     * Api редактирования настройки
     *
     * @Route("/api/admin/setting", name="api_admin_setting_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(
     *         property="name",
     *         type="string",
     *         description="Название параметра",
     *         example="Вконтакте"
     *       ),
     *       @OA\Property(property="setting_id", type="integer", description="setting_id", example="1"),
     *       @OA\Property(property="code", type="string", description="Описание параметра", example="vk_link"),
     *       @OA\Property(property="value", type="string", description="Значение параметра", example="vk.com"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Параметр успешно изменен")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin settings")
     * @Security(name="Bearer")
     */
    public function adminEditSettingAction(
        Request $request,
        SettingsServices $settingsServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $settings['setting_id'] = (int)$this->getJson($request, 'setting_id');
        $settings['name'] = (string)$this->getJson($request, 'name');
        $settings['code'] = (string)$this->getJson($request, 'code');
        $settings['value'] = (string)$this->getJson($request, 'value');

        if (empty($settings['setting_id'])) {
            return $this->jsonError(['setting_id' => "Нужно указать setting_id"], 400);
        }

        $form = $this->createFormByArray(SiteSettingsType::class, $settings);

        if ($form->isValid()) {
            try {
                $settingsServices->updateSetting($form);
            } catch (LogicException $e) {
                return $this->jsonError(['setting_id' => $e->getMessage()], 400);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Параметр успешно изменен"]);
    }

    /**
     * Api создание настройки
     *
     * @Route("/api/admin/setting", name="api_admin_setting_create", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="name", type="string", description="Название параметра", example="Вконтакте"),
     *       @OA\Property(property="code", type="string", description="Описание параметра", example="vk_link"),
     *       @OA\Property(property="value", type="string", description="Значение параметра", example="vk.com"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Параметр успешно изменен")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin settings")
     * @Security(name="Bearer")
     */
    public function adminCreateSettingAction(
        Request $request,
        SettingsServices $settingsServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $settings['setting_id'] = 0;
        $settings['name'] = (string)$this->getJson($request, 'name');
        $settings['code'] = (string)$this->getJson($request, 'code');
        $settings['value'] = (string)$this->getJson($request, 'value');
        $form = $this->createFormByArray(SiteSettingsType::class, $settings);

        if ($form->isValid()) {
            try {
                $settingsServices->updateSetting($form);
            } catch (LogicException $e) {
                return $this->jsonError(["seo_id" => $e->getMessage()], 400);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Параметр успешно создан"]);
    }

    /**
     * Api удаления настройки
     *
     * @Route("/api/admin/setting/{id}",
     *      requirements={"id"="\d+"},
     *      name="api_admin_site_settings_delete",
     *      methods={"DELETE"}
     * )
     *
     * @OA\Parameter(name="id", in="path", description="ID настройки",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Параметр успешно удален")
     * @OA\Response(response=401, description="Ошибка удаления")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin settings")
     * @Security(name="Bearer")
     */
    public function deleteAction(
        Request $request,
        SettingsServices $settingsServices,
        CoreSecurity $security,
        int $id
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        try {
            $settingsServices->deleteSetting($id);
        } catch (LogicException $e) {
            return $this->jsonError(['id' => "Параметр с таким id не найден!"], 400);
        }

        return $this->jsonSuccess(['result' => "Параметр успешно удален"]);
    }

    /**
     * Api получения настройки по ID
     *
     * @Route("/api/admin/setting/{id}", requirements={"id"="\d+"}, name="api_admin_site_setting_info", methods={"GET"})
     *
     * @OA\Parameter(name="id", in="path", description="ID настройки",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Параметры успешно получены")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin settings")
     * @Security(name="Bearer")
     */
    public function adminGetSettingAction(
        $id,
        SettingsServices $settingsServices,
        CoreSecurity $security
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $result = $settingsServices->getSettingById(intval($id));
        if (!$result) {
            return $this->jsonError(['id' => "Параметр с таким id не найден!"], 400);
        }

        return $this->jsonSuccess(['result' => $result]);
    }
}
