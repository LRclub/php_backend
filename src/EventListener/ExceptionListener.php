<?php

// src/EventListener/ExceptionListener.php
namespace App\EventListener;

use App\Security\ApiKeyAuthenticator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Environment;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use App\Security\SecurityAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class ExceptionListener implements EventSubscriberInterface
{
    private $engine;
    private UserAuthenticatorInterface $authenticator;
    private ApiKeyAuthenticator $apiKeyAuthenticator;

    public function __construct(
        Environment $engine,
        UserAuthenticatorInterface $authenticator,
        ApiKeyAuthenticator $apiKeyAuthenticator
    ) {
        $this->engine = $engine;
        $this->authenticator = $authenticator;
        $this->apiKeyAuthenticator = $apiKeyAuthenticator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $data = [];

        if ($exception instanceof NotFoundHttpException) {
            $data = [
                'error_title' => 'Страница не найдена',
                'error_code' => 404,
                'template_path' => 'error/404.html.twig'
            ];
        }

        if ($exception instanceof AccessDeniedException) {
            $data = [
                'error_title' => 'Доступ запрещен',
                'error_code' => 403,
                'template_path' => 'error/403.html.twig'
            ];
        }

        if ($exception instanceof AuthenticationException) {
            $data = [
                'error_title' => 'Доступ запрещен',
                'error_code' => 401,
                'template_path' => 'error/401.html.twig'
            ];
        }

        if (!empty($data)) {
            //Костыль для авторизации при системных ошибках, слетает авторизация в Симфони
            //в начале попадает в этот метод, затем в авторизацию и в шаблонах юзер не авторизован
            $user = $this->apiKeyAuthenticator->getUserByRequest($event->getRequest(), false);
            if ($user) {
                $this->authenticator->authenticateUser($user, $this->apiKeyAuthenticator, $event->getRequest());
            }

            $event->setResponse(new Response($this->engine->render($data['template_path'], [
                'title' => $data['error_title']
            ]), $data['error_code']));
            return;
        }

        if ($this->engine->getGlobals()['app']->getEnvironment() == 'prod') {
            $event->setResponse(new Response($this->engine->render('error/500.html.twig', [
                'title' => 'Ошибка сервера'
            ]), 500));
        }

        return;
    }
}
