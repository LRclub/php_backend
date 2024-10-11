<?php

namespace App\Security;

use App\Entity\User;
use App\Services\User\TokenServices;
use App\Services\User\UserServices;
// Components
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Twig\Environment;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    private const TOKEN_REFRESH_ROUTE = 'api_token_refresh';
    private const ADMIN_USER_ID = 'admin_user_id';

    protected TokenServices $tokenServices;
    protected UserServices $userServices;
    protected UserRepository $userRepository;
    private ParameterBagInterface $params;
    protected $engine;

    public function __construct(
        TokenServices $tokenServices,
        UserServices $userServices,
        UserRepository $userRepository,
        Environment $engine,
        ParameterBagInterface $params
    ) {
        $this->tokenServices = $tokenServices;
        $this->userServices = $userServices;
        $this->userRepository = $userRepository;
        $this->engine = $engine;
        $this->params = $params;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        //исключение для роута обновления токена
        if ($request->get('_route') == self::TOKEN_REFRESH_ROUTE) {
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): PassportInterface
    {
        $user = $this->getUserByRequest($request);

        $this->testCaseBuild($user, $request);

        // Если админ заходит под пользователем и есть кука admin_user_token
        if ($user->getIsAdmin() && $request->cookies->get(self::ADMIN_USER_ID)) {
            $user = $this->userRepository->find(intval($request->cookies->get(self::ADMIN_USER_ID)));
            if ($user) {
                return new SelfValidatingPassport(
                    new UserBadge($user->getUserIdentifier(), function (string $userIdentifier) use ($user) {
                        $user->setIsLoggedAdmin(true);
                        return $user;
                    })
                );
            }
        }

        // save visit info
        $this->userServices->saveUserAuthTime($user);

        //update last visit time
        $this->userServices->updateLastVisit($user);

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier()));
    }

    public function getUserByRequest(Request $request, $throwExceptions = true)
    {
        $tokenData = $this->tokenServices->getRawToken($request);

        if ($tokenData === null) {
            $this->userServices->logout();

            if ($throwExceptions) {
                throw new CustomUserMessageAuthenticationException('Отсутствует авторизационный токен');
            }
            return;
        }

        $user_identifier = $this->tokenServices->checkUserToken($tokenData['user_id'], $tokenData['token']);

        if ($user_identifier === null) {
            $this->userServices->logout();

            if ($throwExceptions) {
                throw new CustomUserMessageAuthenticationException('Не удалось авторизироваться по токену');
            }
            return;
        }

        if ($user_identifier->getUser()->getIsBlocked()) {
            $this->userServices->logout();

            if ($throwExceptions) {
                throw new CustomUserMessageAuthenticationException('Пользователь заблокирован');
            }
            return;
        }


        return $user_identifier->getUser();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Проверка на тест кейс
     * Если это тестовый кейс, то выставляем куку TEST_VALUE
     *
     * @param mixed $user
     *
     * @return [type]
     */
    public function testCaseBuild(User $user, Request $request)
    {
        // Если билд имеет значение тест кейса и пользователь попадает для теста
        if (str_ends_with($user->getId(), 0)) {
            // Выставляем куку
            setcookie('ab_test', 'test', time() + 360000, '/');
            return true;
        }

        setcookie('ab_test', '', -1, '/');
        return false;
    }
}
