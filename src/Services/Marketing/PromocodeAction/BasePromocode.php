<?php

namespace App\Services\Marketing\PromocodeAction;

use App\Entity\Invoice;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Interfaces\PromocodesInterface;
use App\Services\Marketing\PromocodeServices;
use App\Entity\Promocodes;

abstract class BasePromocode implements PromocodesInterface
{
    private PromocodeServices $promocodeServices;
    private Promocodes $promocode;

    public function registration(UserInterface $user)
    {
        return false;
    }

    public function payment(Invoice $invoice)
    {
        return false;
    }

    public function setPromocodeService(PromocodeServices $promocodeServices)
    {
        $this->promocodeServices = $promocodeServices;

        return $this;
    }

    public function setPromocode(Promocodes $promocode)
    {
        $this->promocode = $promocode;

        return $this;
    }

    protected function getPromocodeService()
    {
        return $this->promocodeServices;
    }

    protected function getPromocode()
    {
        return $this->promocode;
    }
}
