<?php

namespace App\Services\Marketing\PromocodeAction;

use App\Interfaces\PromocodesInterface;
use App\Entity\Promocodes;
use App\Entity\Invoice;
use App\Services\Marketing\PromocodeAction\BasePromocode;
use App\Services\Marketing\PromocodeServices;
use App\Services\User\UserServices;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

class InviteAction extends BasePromocode implements PromocodesInterface
{
    private UserServices $userServices;
    private EntityManagerInterface $em;

    public function __construct(
        UserServices $userServices,
        EntityManagerInterface $em
    ) {
        $this->userServices = $userServices;
        $this->em = $em;
    }

    /**
     * @param mixed $promocode
     * @param UserInterface $user
     *
     * @return bool
     */
    public function registration(UserInterface $user)
    {
        $promocode = $this->getPromocode();
        if ($promocode->getOwner()) {
            $user->setInvited($promocode->getOwner());
            $this->em->persist($user);
            $this->em->flush();
        }
    }
}
