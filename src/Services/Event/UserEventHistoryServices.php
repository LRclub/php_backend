<?php

namespace App\Services\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\UserEventHistory;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\SystemServices;

class UserEventHistoryServices
{
    private ParameterBagInterface $params;
    private EntityManagerInterface $em;
    private SystemServices $systemServices;

    public function __construct(
        EntityManagerInterface $em,
        ParameterBagInterface $params,
        SystemServices $systemServices
    ) {
        $this->params = $params;
        $this->em = $em;
        $this->systemServices = $systemServices;
    }

    /**
     * Сохранение действия пользователя
     *
     * @param mixed $user
     * @param mixed $action
     * @param int $amount
     *
     * @return UserEventHistory
     */
    public function saveUserEvent($user, $action, $amount = 0): UserEventHistory
    {
        $user_header_info = $this->systemServices->getUserDeviceInfo();

        $ab_test = $this->params->get('ab.test');

        $event = new UserEventHistory();
        $event
            ->setUser($user)
            ->setAction($action)
            ->setCompletionTime(time())
            ->setAmount($amount)
            ->setUserAgent($user_header_info['user_agent'])
            ->setDeviceType($user_header_info['device_type'])
            ->setDeviceOs($user_header_info['device_os'])
            ->setPlatform($user_header_info['platform'])
            ->setABTest($ab_test);

        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }
}
