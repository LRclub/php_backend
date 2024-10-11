<?php

namespace App\Controller\Api\MobileClient;

use App\Controller\Base\BaseApiController;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\Notice\NoticeServices;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\MobileClient\MobileClientServices;

class MobileClientController extends BaseApiController
{
    /**
     * Сохранение мобильного client ID
     *
     * @Route("api/mobile/client_id", name="api_mobile_client_id", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="client_id", type="string", description="ID клиента", example="3624506262018561756")
     *   )
     *  )
     * )
     *
     * @OA\Response(response=200, description="Client id сохранен")
     * @OA\Response(response=401, description="Необходима авторизация")
     * @OA\Response(response=404, description="Ошибка сохранения")
     *
     * @OA\Tag(name="mobile")
     * @Security(name="Bearer")
     */
    public function mobileClientAction(
        CoreSecurity $security,
        Request $request,
        MobileClientServices $mobileClientServices
    ): Response {
        $user = $security->getUser();

        $client_id = (string)$this->getJson($request, 'client_id');

        try {
            $mobileClientServices->saveMobileClientId($user, $client_id);
        } catch (LogicException $e) {
            return $this->jsonError(['#' => $e->getMessage()]);
        }

        return $this->jsonSuccess();
    }
}
