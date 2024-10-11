<?php

namespace App\Interfaces;

use App\Entity\Invoice;
use App\Entity\Promocodes;
use Proxies\__CG__\App\Entity\Invoice as EntityInvoice;
use Symfony\Component\Security\Core\User\UserInterface;

interface PromocodesInterface
{
    /**
     * @param UserInterface $user
     *
     * @return bool
     */
    public function registration(UserInterface $user);

    /**
     * @param UserInterface $user
     * @param Invoice $invoice
     *
     * @return bool
     */
    public function payment(Invoice $invoice);
}
