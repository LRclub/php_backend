<?php

namespace App\Controller\Chat;

use App\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\Chat\ChatServices;

class ChatController extends BaseApiController
{
    /**
     * Страница выбора чатов
     *
     * @Route("/panel/chat", name="chat", methods={"GET"})
     */
    public function chatAction(CoreSecurity $security): Response
    {
        return $this->render('/pages/chat/chat.html.twig', []);
    }
}
