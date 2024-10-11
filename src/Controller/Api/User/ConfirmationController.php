<?php

namespace App\Controller\Api\User;

use App\Controller\Base\BaseApiController;
use App\Repository\UserRepository;
use App\Services\QueueServices;
use App\Services\User\EmailConfirmationServices;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class ConfirmationController extends BaseApiController
{
    /**
     * Повторная отправка ссылки подтверждения
     *
     * @Route("/api/user/resend", name="api_confirmation_resend", methods={"POST"})
     *
     * @OA\RequestBody(
     *   @OA\MediaType(
     *     mediaType="application/json",
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="email", type="string", description="E-mail пользователя", example="email@test.ru"),
     *     )
     *   )
     * )
     *
     * @OA\Response(response=200, description="Успешно отправлено")
     * @OA\Response(response=400, description="E-mail не корректный")
     * @OA\Response(response=403, description="E-mail уже подтвержден")
     * @OA\Response(response=429, description="Необходимо подождать до следующей отправки")
     * @OA\Response(response=404, description="E-mail не найден")
     *
     * @OA\Tag(name="restore")
     * @Security(name="Bearer")
     */
    public function resendAction(
        Request $request,
        UserRepository $userRepository,
        EmailConfirmationServices $emailConfirmationServices,
        QueueServices $QueueServices
    ): Response {
        $email = (string)$this->getJson($request, 'email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonError(['email' => 'Введите корректный e-mail!']);
        }

        $user = $userRepository->findByEmail($email);

        if ($user === null) {
            return $this->jsonError(['email' => 'E-mail не найден!'], 404);
        }

        $last_request = $emailConfirmationServices->getLastTimeSend($user);

        if ($last_request && $last_request->getCreateTime() > time() - 300) {
            return $this->jsonError([
                'email' => 'Вы недавно отправляли запрос на подтверждение. Пожалуйста, подождите.'
            ], 429);
        }

        if (!$user->getIsConfirmed()) {
            $confirmation = $emailConfirmationServices->createCode($user);

            $QueueServices->sendEmail(
                $user->getEmail(),
                'Повторное письмо активации',
                '/mail/user/registration/resend_code.html.twig',
                [
                    'code' => $confirmation->getCode(),
                    'user_id' => $user->getId()
                ]
            );

            return $this->jsonSuccess();
        } else {
            return $this->jsonError(['email' => 'E-mail уже подтвержден!'], 403);
        }
    }
}
