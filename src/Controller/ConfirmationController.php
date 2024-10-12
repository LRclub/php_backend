<?php

namespace App\Controller;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\Specialist\RequestsServices;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use App\Services\User\EmailConfirmationServices;
use App\Services\Event\UserEventHistoryServices;

class ConfirmationController extends BaseApiController
{
    private UserEventHistoryServices $userEventHistoryServices;

    public function __construct(
        UserEventHistoryServices $userEventHistoryServices
    ) {
        $this->userEventHistoryServices = $userEventHistoryServices;
    }

    /**
     * Подтверждение e-mail пользователя
     *
     *
     */
     @Route("/confirmation/{user_id}/{code}", name="confirmation", methods={"GET"}, requirements={"user_id"="\d+"})
     
    public function confirmationAction(
        EmailConfirmationServices $emailConfirmationServices,
        $user_id,
        $code
    ): Response {
        $confirmation = $emailConfirmationServices->checkCode($user_id, $code);

        if ($confirmation && !$confirmation->getUser()->getIsConfirmed()) {
            $emailConfirmationServices->userConfirm($confirmation);
            $this->userEventHistoryServices->saveUserEvent($confirmation->getUser(), 'mail_confirmation');

            return $this->redirect('/?mail_confirmation_success');
        } else {
            return $this->redirect('/?mail_confirmation_failed');
        }
    }
}
