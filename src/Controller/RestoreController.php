<?php

namespace App\Controller;

use App\Controller\Base\BaseApiController;
use App\Repository\UserRepository;
use App\Services\User\UserServices;
use App\Services\User\RestoreServices;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\RandomizeServices;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Services\QueueServices;

class RestoreController extends BaseApiController
{
    /**
     * Смена номера
     *
     * @Route("/restore/", name="restore_phone", methods={"GET"})
     */
    public function restoreAction(): Response
    {
        return $this->render('/pages/restore/phone/restore_phone.html.twig', [
            'title' => 'Смена номера'
        ]);
    }

    /**
     * Актуальность данных для смены номера
     *
     * @Route("/restore/{user_id}/{code}", name="restore_confirmation", requirements={"user_id"="\d+"}, methods={"GET"})
     */
    public function restoreConfirmationAction(int $user_id, string $code, RestoreServices $restoreServices): Response
    {
        $status = $restoreServices->checkCode($user_id, $code);

        return $this->render('pages/restore/phone/confirmation_phone.html.twig', [
            'title' => "Подтверждение смены номера",
            'status' => $status,
            'user_id' => $user_id,
            'code' => $code
        ]);
    }
}
