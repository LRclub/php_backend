<?php

namespace App\Controller\Api\Materials;

use App\Controller\Base\BaseApiController;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\LogicException;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use App\Services\Admin\AdminMaterialsServices;
use App\Form\Materials\MaterialsCreateType;
use App\Services\Materials\MaterialsServices;

class MaterialsController extends BaseApiController
{
    /**
     * Создать материал
     *
     * @Route("/api/admin/material", name="api_admin_material_create", methods={"PUT"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="title", type="text", description="Название материала", example="Текст"),
     *       @OA\Property(property="description", type="text", description="Описание", example="Текст"),
     *       @OA\Property(property="short_description", type="text", description="Краткое описание", example="Текст"),
     *       @OA\Property(property="preview_image_id", type="integer", description="Превью изображение", example="50"),
     *       @OA\Property(property="access", type="integer", description="Доступ", example="0"),
     *       @OA\Property(property="lazy_post", type="text", description="Отложенная запись", example="2024-10-16"),
     *       @OA\Property(property="is_show_bill", type="boolval", description="Публикация в афише", example="1"),
     *       @OA\Property(property="category_id", type="integer", description="ID категории", example="1"),
     *       @OA\Property(property="type", type="string", description="Тип материала", example="audio"),
     *       @OA\Property(property="video_id", type="integer", description="ID видео файла", example="51"),
     *       @OA\Property(property="audio_id", type="integer", description="ID аудио файла", example="52"),
     *       @OA\Property(property="article_files",
     *          type="array",
     *          description="Идентификаторы файлов для статьи",
     *          example="[1,2,4]",
     *          @OA\Items(type="integer", format="int32")
     *       ),
     *       @OA\Property(property="stream_url", type="text", description="Ссылка на запись стрима", example=""),
     *       @OA\Property(property="stream_start", type="text", description="Дата начала эфира", example="2024-10-16"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Материал успешно добавлен")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=400, description="Ошибка валидации")
     *
     * @OA\Tag(name="materials")
     * @Security(name="Bearer")
     */
    public function materialAddAction(
        Request $request,
        CoreSecurity $security,
        AdminMaterialsServices $adminMaterialsServices
    ): Response {
        $user = $security->getUser();

        if (!$user->getIsEditor() && !$user->getIsAdmin()) {
            return $this->jsonError(['roles' => "Запрещено, нет прав"], 403);
        }

        $material = $this->dataPreparation($request);

        $form = $this->createFormByArray(MaterialsCreateType::class, $material);

        if ($form->isValid()) {
            try {
                $material = $adminMaterialsServices->addMaterial($form, $user);
            } catch (LogicException $e) {
                return $this->jsonError([$e->getMessage()], 400);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => true]);
    }

    /**
     * Редактировать материал
     *
     * @Route("/api/admin/material", name="api_admin_material_edit", methods={"PATCH"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="material_id", type="integer", description="ID материала", example="1"),
     *       @OA\Property(property="title", type="text", description="Название материала", example="Текст"),
     *       @OA\Property(property="description", type="text", description="Описание", example="Текст"),
     *       @OA\Property(property="short_description", type="text", description="Краткое описание", example="Текст"),
     *       @OA\Property(property="preview_image_id", type="integer", description="Превью изображение", example="50"),
     *       @OA\Property(property="access", type="integer", description="Доступ", example="0"),
     *       @OA\Property(property="lazy_post", type="text", description="Отложенная запись", example="2024-10-16"),
     *       @OA\Property(property="is_show_bill", type="boolval", description="Публикация в афише", example="1"),
     *       @OA\Property(property="category_id", type="integer", description="ID категории", example="1"),
     *       @OA\Property(property="type", type="string", description="Тип материала", example="audio"),
     *       @OA\Property(property="video_id", type="integer", description="ID видео файла", example="51"),
     *       @OA\Property(property="audio_id", type="integer", description="ID аудио файла", example="52"),
     *       @OA\Property(property="article_files",
     *          type="array",
     *          description="Идентификаторы файлов для статьи",
     *          example="[1,2,4]",
     *          @OA\Items(type="integer", format="int32")
     *       ),
     *       @OA\Property(property="stream_url", type="text", description="Ссылка на запись стрима", example=""),
     *       @OA\Property(property="stream_start", type="text", description="Дата начала эфира", example="2024-10-16"),
     *       @OA\Property(property="is_stream_finished", type="boolval", description="Эфир завершен?", example="false")
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Материал успешно изменен")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     * @OA\Response(response=400, description="Ошибка валидации")
     *
     * @OA\Tag(name="materials")
     * @Security(name="Bearer")
     */
    public function materialEditAction(
        Request $request,
        CoreSecurity $security,
        AdminMaterialsServices $adminMaterialsServices
    ): Response {
        $user = $security->getUser();

        if (!$user->getIsEditor() && !$user->getIsAdmin()) {
            return $this->jsonError(['roles' => "Запрещено, нет прав"], 403);
        }

        $material = $this->dataPreparation($request);
        $material['material_id'] = $this->getIntOrNull($this->getJson($request, 'material_id'));
        $material['is_stream_finished'] = (bool)$this->getJson($request, 'is_stream_finished');

        $form = $this->createFormByArray(MaterialsCreateType::class, $material);

        if ($form->isValid()) {
            try {
                $material = $adminMaterialsServices->editMaterial($form, $user);
            } catch (LogicException $e) {
                return $this->jsonError([$e->getMessage()], 400);
            }
        } else {
            $errors = $this->getErrorMessages($form);
            return $this->jsonError($errors);
        }

        return $this->jsonSuccess(['result' => true]);
    }

    /**
     * Удаление материала
     *
     * @Route("/api/admin/material/{id}",
     *  requirements={"id"="\d+"},
     *  name="api_admin_material_delete",
     *  methods={"DELETE"}
     * )
     *
     * @OA\Parameter(name="id", in="path", description="ID материала",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Материал успешно удален")
     * @OA\Response(response=400, description="Материал не найден")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="materials")
     * @Security(name="Bearer")
     */
    public function materialDeleteAction(
        Request $request,
        CoreSecurity $security,
        AdminMaterialsServices $adminMaterialsServices,
        $id = null
    ) {
        $user = $security->getUser();
        if (!$user->getIsAdmin()) {
            return $this->jsonError(['role' => "Ошибка прав"], 403);
        }

        try {
            $adminMaterialsServices->deleteMaterial((int)$id);
        } catch (LogicException $e) {
            return $this->jsonError(['material_id' => $e->getMessage()], 400);
        }
        return $this->jsonSuccess(['result' => "Материал успешно удален"]);
    }

    /**
     * Получение ключа для стрима
     *
     * @Route("/api/materials/stream/{material_id}",
     *          name="user_stream_auth",
     *          requirements={"id"="\d+"},
     *          methods={"GET"}
     *        )
     *
     *
     * @OA\Response(response=200, description="Ключ для стрима успешно получен")
     * @OA\Response(response=400, description="Ошибка")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="materials")
     * @Security(name="Bearer")
     */
    public function userStreamAuth(
        Request $request,
        MaterialsServices $materialsServices,
        CoreSecurity $security,
        $material_id = null
    ) {
        $user = $security->getUser();

        try {
            $key = $materialsServices->getUserStreamKey($user, $material_id);
        } catch (LogicException $e) {
            return $this->jsonError(['#' => $e->getMessage()], 400);
        }

        return $this->jsonSuccess(['result' => "$key"]);
    }

    /**
     * Список материалов
     *
     * @Route("/api/materials", name="api_materials", methods={"GET"})
     *
     * @OA\Parameter(
     *   in="query", name="category", schema={"type"="string", "example"="yoga"}, description="Slug категории"
     *  ),
     * @OA\Parameter(
     *  in="query", name="page", schema={"type"="integer", "example"=1}, description="Номер страниц (По умолчанию 1)"
     *  ),
     * @OA\Parameter(
     *  in="query", name="sort", schema={"type"="string", "example"="views_count"}, description="Параметр сортировки"
     *  )
     * @OA\Parameter(
     *  in="query", name="order", schema={"type"="string", "example"="desc"}, description="Тип сортировки"
     *  )
     * @OA\Parameter(
     *  in="query", name="type", schema={"type"="string", "example"="stream"}, description="Тип материала"
     *  )
     *
     * @OA\Response(response=200, description="Материалы предоставлены")
     * @OA\Response(response=400, description="Материалы не найден")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="materials")
     * @Security(name="Bearer")
     */
    public function materialsAction(
        CoreSecurity $security,
        Request $request,
        MaterialsServices $materialsServices
    ): Response {
        $user = $security->getUser();

        // Категория
        $category = trim($request->query->get('category'));

        $filter = $materialsServices->getMaterialsFilterData($request, $category);

        $materials = $materialsServices->getMaterials($user, $filter);
        $materials_count = (int)$materialsServices->getMaterials(
            $user,
            $filter,
            true,
        );

        return $this->jsonSuccess([
            'result' => $materials,
            'category' => $category,
            'materials_count' => $materials_count,
            'current_page' => $filter['page'],
            'pages_count' => round($materials_count / $materialsServices::PAGE_OFFSET)
        ]);
    }

    /**
     * Получение материала по ID
     *
     * @Route("/api/material/{id}", requirements={"id"="\d+"}, name="api_material", methods={"GET"})
     *
     * @OA\Parameter(name="id", in="path", description="ID материала",
     *     @OA\Schema(type="integer", example="1")
     * )
     *
     * @OA\Response(response=200, description="Материал предоставлен")
     * @OA\Response(response=400, description="Материал не найден")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="materials")
     * @Security(name="Bearer")
     */
    public function materialAction(
        CoreSecurity $security,
        MaterialsServices $materialsServices,
        int $id
    ): Response {
        $user = $security->getUser();
        $material = $materialsServices->getMaterialById($id, $user);
        if (!$material) {
            return $this->jsonError(['material' => "Материал не найден"]);
        }

        return $this->jsonSuccess([
            'result' => $material
        ]);
    }

    /**
     * Список материалов для афиши
     *
     * @Route("/api/materials/showbill", name="api_materials_showbill", methods={"GET"})
     *
     * @OA\Parameter(
     *  in="query", name="sort", schema={"type"="string", "example"="date"}, description="Параметр сортировки"
     *  )
     * @OA\Parameter(
     *  in="query", name="order", schema={"type"="string", "example"="desc"}, description="Тип сортировки"
     *  )
     *
     * @OA\Response(response=200, description="Материалы предоставлены")
     * @OA\Response(response=400, description="Материалы не найдены")
     * @OA\Response(response=403, description="Запрещено, нет прав")
     *
     * @OA\Tag(name="materials")
     * @Security(name="Bearer")
     */
    public function materialsShowBillAction(
        CoreSecurity $security,
        Request $request,
        MaterialsServices $materialsServices
    ): Response {
        $user = $security->getUser();
        // Сортировка
        $order_by['sort_param'] = mb_strtolower($request->query->get('sort'));
        $order = mb_strtolower($request->query->get('order'));
        $order_by['sort_type'] = ($order == self::SORT_ASC) ? self::SORT_ASC : self::SORT_DESC;

        $materials = $materialsServices->getShowBillMaterials($user, $order_by);

        return $this->jsonSuccess([
            'result' => $materials
        ]);
    }

    /**
     * Получение данных для материала
     *
     * @param mixed $request
     *
     * @return [type]
     */
    private function dataPreparation($request)
    {
        $material = [
            'material_id' => null,
            'title' => (string)$this->getJson($request, 'title'),
            'description' => (string)$this->getJson($request, 'description'),
            'short_description' => (string)$this->getJson($request, 'short_description'),
            'preview_image_id' => $this->getIntOrNull($this->getJson($request, 'preview_image_id')),
            'access' => (int)$this->getJson($request, 'access'),
            'lazy_post' => (string)$this->getJson($request, 'lazy_post'),
            'is_show_bill' => (bool)$this->getJson($request, 'is_show_bill'),
            'category_id' => $this->getIntOrNull($this->getJson($request, 'category_id')),
            'type' => (string)$this->getJson($request, 'type'),
            'video_id' => $this->getIntOrNull($this->getJson($request, 'video_id')),
            'audio_id' => $this->getIntOrNull($this->getJson($request, 'audio_id')),
            'article_files' => (array)$this->getJson($request, 'article_files') ?? [],
            'stream_url' => trim($this->getJson($request, 'stream_url')),
            'stream_start' => (string)$this->getJson($request, 'stream_start'),
            'is_stream_finished' => false,
        ];

        return $material;
    }
}
