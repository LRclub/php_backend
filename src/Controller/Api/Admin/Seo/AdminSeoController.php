<?php

namespace App\Controller\Api\Admin\Seo;

use App\Entity\Seo;
use App\Form\SeoType;
use App\Repository\SeoRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\Seo\SeoServices;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use App\Controller\Base\BaseApiController;
use OpenApi\Annotations as OA;

class AdminSeoController extends BaseApiController
{
    /**
     * Api редактирования SEO настройки
     *
     * @Route("/api/admin/seo", name="api_admin_seo_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="seo_id", type="integer", description="seo_id", example="1"),
     *       @OA\Property(property="link", type="string", description="Ссылка", example="/"),
     *       @OA\Property(property="title", type="string", description="Seo заголовок", example="Скелетон"),
     *       @OA\Property(property="description", type="string", description="Описание", example="Сайт скелетон"),
     *       @OA\Property(property="keywords", type="string", description="Ключевые слова", example="php, dev, linux"),
     *       @OA\Property(property="title_page",
     *          type="string",
     *          description="Заголовок страницы",
     *          example="Главная страница"
     *       ),
     *       @OA\Property(property="property",
     *          type="array",
     *          description="Параметры",
     *          example={{
     *                  "name": "viewport",
     *                  "content": "width=1000"
     *                }, {
     *                  "name":  "viewport",
     *                  "content": "width=1000"
     *                }, {
     *                  "name":  "viewport",
     *                  "content": "width=1000"
     *                }},
     *          @OA\Items(
     *                      @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Параметр"
     *                      ),
     *                      @OA\Property(
     *                         property="content",
     *                         type="string",
     *                         example="Контент"
     *                      )
     *                ),
     *       )
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="SEO параметр успешно изменен")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin seo")
     * @Security(name="Bearer")
     */
    public function adminEditSeoAction(
        Request $request,
        SeoServices $seoServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        $settings['seo_id'] = (int)$this->getJson($request, 'seo_id');
        $settings['link'] = (string)$this->getJson($request, 'link');
        $settings['title'] = (string)$this->getJson($request, 'title');
        $settings['description'] = (string)$this->getJson($request, 'description');
        $settings['keywords'] = (string)$this->getJson($request, 'keywords');
        $settings['property'] = (array)$this->getJson($request, 'property');
        $settings['title_page'] = (string)$this->getJson($request, 'title_page');

        if (empty($settings['seo_id'])) {
            return $this->jsonError(['seo_id' => "Нужно указать seo_id"], 400);
        }

        $form = $this->createFormByArray(SeoType::class, $settings);

        if ($form->isValid()) {
            try {
                $seoServices->updateSeo($form);
            } catch (LogicException $e) {
                return $this->jsonError(['seo_id' => $e->getMessage()], 400);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors, 400);
        }

        return $this->jsonSuccess(['result' => "Seo Параметр успешно изменен"]);
    }

    /**
     * Api создание SEO настройки
     *
     * @Route("/api/admin/seo", name="api_admin_seo_create", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(type="object",
     *       @OA\Property(property="link", type="string", description="Ссылка", example="/"),
     *       @OA\Property(property="title", type="string", description="Seo заголовок", example="Скелетон"),
     *       @OA\Property(property="description", type="string", description="Описание", example="Сайт скелетон"),
     *       @OA\Property(property="keywords", type="string", description="Ключевые слова", example="php, dev, linux"),
     *       @OA\Property(
     *         property="title_page",
     *         type="string",
     *         description="Заголовок страницы",
     *         example="Главная страница"
     *       ),
     *       @OA\Property(property="property",
     *          type="array",
     *          description="Параметры",
     *          example={{
     *                  "name": "viewport",
     *                  "content": "width=1000"
     *                }, {
     *                  "name":  "viewport",
     *                  "content": "width=1000"
     *                }, {
     *                  "name":  "viewport",
     *                  "content": "width=1000"
     *                }},
     *          @OA\Items(
     *                      @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Параметр"
     *                      ),
     *                      @OA\Property(
     *                         property="content",
     *                         type="string",
     *                         example="Контент"
     *                      )
     *                ),
     *       )
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="SEO Параметр успешно создан")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin seo")
     * @Security(name="Bearer")
     */
    public function adminCreateSeoAction(
        Request $request,
        SeoServices $seoServices,
        CoreSecurity $security
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(["role" => "Доступ запрещен"], 403);
        }

        $settings['seo_id'] = 0;
        $settings['link'] = (string)$this->getJson($request, 'link');
        $settings['title'] = (string)$this->getJson($request, 'title');
        $settings['description'] = (string)$this->getJson($request, 'description');
        $settings['keywords'] = (string)$this->getJson($request, 'keywords');
        $settings['property'] = (array)$this->getJson($request, 'property');
        $settings['title_page'] = (string)$this->getJson($request, 'title_page');

        $form = $this->createFormByArray(SeoType::class, $settings);

        if ($form->isValid()) {
            try {
                $seoServices->updateSeo($form);
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
     * Api удаления SEO настройки
     *
     * @Route("/api/admin/seo/{id}", requirements={"id"="\d+"}, name="api_admin_site_seo_delete", methods={"DELETE"})
     *
     * @OA\Parameter(name="id", in="path", description="ID seo настройки",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="SEO Параметр успешно удален")
     * @OA\Response(response=401, description="Ошибка удаления")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin seo")
     * @Security(name="Bearer")
     */
    public function adminDeleteSeoAction(
        Request $request,
        SeoServices $seoServices,
        CoreSecurity $security,
        int $id
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        if (empty($id)) {
            return $this->jsonError(["id" => "Нужно указать seo_id"], 400);
        }

        try {
            $seoServices->deleteSeo($id);
        } catch (LogicException $e) {
            return $this->jsonError(["id" => "Параметр с таким id не найден!"], 400);
        }

        return $this->jsonSuccess(['result' => "Параметр успешно удален"]);
    }

    /**
     * Api получения SEO настройки по ID
     *
     * @Route("/api/admin/seo/{id}", requirements={"id"="\d+"}, name="api_admin_site_seo_info", methods={"GET"})
     *
     * @OA\Parameter(name="id", in="path", description="ID seo настройки",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Параметры успешно получены")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="admin seo")
     * @Security(name="Bearer")
     */
    public function adminGetSeoAction(
        SeoServices $seoServices,
        CoreSecurity $security,
        int $id
    ): Response {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }
        $result = $seoServices->getSeoById(intval($id));
        if (!$result) {
            return $this->jsonError(["id" => "Параметр с таким id не найден!"], 400);
        }

        return $this->jsonSuccess(['result' => $result]);
    }
}
