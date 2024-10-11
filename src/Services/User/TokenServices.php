<?php

namespace App\Services\User;

use App\Entity\AuthToken;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\AuthTokenRepository;
use App\Repository\RefreshTokenRepository;
use App\Services\RandomizeServices;
use Doctrine\ORM\EntityManagerInterface;

class TokenServices
{
    private const AUTH_TOKEN_LIFE = 86400 * 7;
    private const REFRESH_TOKEN_LIFE = 86400 * 31;

    private EntityManagerInterface $em;
    private AuthTokenRepository $authTokenRepository;
    private RefreshTokenRepository $refreshTokenRepository;

    public function __construct(
        EntityManagerInterface $em,
        AuthTokenRepository $authTokenRepository,
        RefreshTokenRepository $refreshTokenRepository
    ) {
        $this->em = $em;
        $this->authTokenRepository = $authTokenRepository;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * Генерация пары токенов - рефреш и авторизационный
     *
     * @param User $user
     * @return array
     */
    public function createPair(User $user)
    {
        $refreshToken = $this->createRefreshToken($user);
        $authToken = $this->createAuthToken($user, $refreshToken);

        return [
            'refresh_token' => $this->concatIdWithHash($user, $refreshToken->getToken()),
            'auth_token' => $this->concatIdWithHash($user, $authToken->getToken())
        ];
    }

    /**
     * Возвращаем данные по токену
     *
     * @param int $user_id
     * @param $token
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkUserToken(int $user_id, $token): ?AuthToken
    {
        return $this->authTokenRepository->findByUserIdAndToken($user_id, $token);
    }

    /**
     * Возвращаем данные по текущему токену
     * @param $request
     * @return array|null
     */
    public function getRawToken($request)
    {
        $headerToken = $request->cookies->get('token');

        if (empty($headerToken) && !empty($request->headers->get('Authorization'))) {
            $headerToken = $request->headers->get('Authorization');

            return $this->getTokenData($headerToken);
        }

        return $headerToken ? $this->getTokenData($headerToken, false) : null;
    }

    /**
     * Возвращаем данные по токену в разобраном виде
     *
     * @param string $authorization
     * @return array|null
     */
    public function getTokenData(string $authorization, bool $bearer_token = true)
    {
        if (!preg_match('/^' . ($bearer_token ? 'Bearer ' : '') . '(\d+):([a-zA-Z0-9]+)$/i', $authorization, $out)) {
            return null;
        }

        return [
            'user_id' => $out[1],
            'token' => $out[2]
        ];
    }

    /**
     * Возвращаем информацию по рефреш токену
     *
     * @param string $refresh_token
     * @return array|null
     */
    public function getDataRefreshToken(string $refresh_token)
    {
        if (!preg_match('/^(\d+):([a-zA-Z0-9]+)$/i', $refresh_token, $out)) {
            return null;
        }

        return [
            'user_id' => $out[1],
            'token' => $out[2]
        ];
    }

    /**
     * Возвращаем информацию о существовании рефреш токена
     *
     * @param int $user_id
     * @param $refresh_token
     * @return RefreshToken|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkRefreshToken(int $user_id, $refresh_token): ?RefreshToken
    {
        return $this->refreshTokenRepository->findByUserIdAndToken($user_id, $refresh_token);
    }

    /**
     * Создаем авторизационнный токен по рефреш токену и идентификатору юзера
     *
     * @param RefreshToken $refreshToken
     * @return string
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function createAuthByRefreshToken(RefreshToken $refreshToken)
    {
        $user = $refreshToken->getUser();
        $auth_token = $refreshToken->getAuthToken();

        $auth_token
            ->setToken($this->generateRandomToken())
            ->setExpirationTime(time() + self::AUTH_TOKEN_LIFE);

        $this->em->persist($auth_token);
        $this->em->flush();

        return $this->concatIdWithHash($user, $auth_token->getToken());
    }

    /**
     * Создание авторизационного токена
     *
     * @param User $user
     * @param RefreshToken $refreshToken
     * @return AuthToken
     */
    private function createAuthToken(User $user, RefreshToken $refreshToken)
    {
        $authToken = new AuthToken();
        $authToken->setUser($user)
            ->setExpirationTime(time() + self::AUTH_TOKEN_LIFE)
            ->setRefreshToken($refreshToken)
            ->setToken($this->generateRandomToken());

        $this->em->persist($authToken);
        $this->em->flush();

        return $authToken;
    }

    /**
     * Приводим к общему формату
     *
     * @param User $user
     * @param string $hash
     * @return string
     */
    private function concatIdWithHash(User $user, string $hash)
    {
        return $user->getId() . ':' . $hash;
    }

    /**
     * Создание рефреш токена
     *
     * @param User $user
     * @return RefreshToken
     */
    private function createRefreshToken(User $user)
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setUser($user)
            ->setExpirationTime(time() + self::REFRESH_TOKEN_LIFE)
            ->setToken($this->generateRandomToken(48));

        $this->em->persist($refreshToken);
        $this->em->flush();

        return $refreshToken;
    }

    /**
     * Генерация случайного токена
     *
     * @param int $len
     * @return string
     */
    private function generateRandomToken(int $len = 32)
    {
        return mb_strtolower(RandomizeServices::generateString($len));
    }
}
