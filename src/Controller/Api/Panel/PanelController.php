<?php

namespace App\Controller\Api\Panel;

use App\Controller\Base\BaseApiController;
use App\Exceptions\ChatAccessDenied;
use App\Services\PanelServices;
use App\Services\User\UserServices;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Annotations as OA;
use Symfony\Component\Security\Core\Exception\LogicException;
use App\Services\Chat\ChatServices;
use App\Form\Chat\ChatMessageType;
use App\Services\QueueServices;
use Symfony\Component\Security\Core\User\UserInterface;

class PanelController extends BaseApiController
{
    /**
     * API данные для панели
     *
     * @Route(path="/api/panel", name="api_panel_data", methods={"GET"})
     *
     * @OA\Response(response=200, description="Ответ успешно получен")
     * @OA\Response(response=401, description="Необходима авторизация")
     *
     * @OA\Tag(name="panel")
     * @Security(name="Bearer")
     */
    public function panelDataAction(
        CoreSecurity $security,
        PanelServices $panelServices
    ) {
        $user = $security->getUser();

        return $this->jsonSuccess(['result' => $panelServices->getData($user)]);
    }
}
