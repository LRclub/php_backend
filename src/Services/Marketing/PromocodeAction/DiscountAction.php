<?php

namespace App\Services\Marketing\PromocodeAction;

use App\Interfaces\PromocodesInterface;
use App\Entity\Invoice;
use App\Entity\Promocodes;
use App\Services\Marketing\PromocodeAction\BasePromocode;
use Symfony\Component\Security\Core\User\UserInterface;

class DiscountAction extends BasePromocode implements PromocodesInterface
{
    private $invoice;

    public function __construct(
        Invoice $invoice = null
    ) {
        $this->invoice = $invoice;
    }

    /**
     * @param mixed $promocode
     * @param UserInterface $user
     * @param Invoice $invoice
     *
     * @return bool
     */
    public function payment(Invoice $invoice)
    {
        return true;
    }
}
