<?php

namespace App\Services\Payment;

use App\Entity\User;
use App\Entity\SubscriptionHistory;
use App\Repository\InvoiceRepository;
use App\Repository\TariffsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SubscriptionHistoryRepository;

class SubscriptionHistoryServices
{
    // Тип операции оплата
    public const TYPE_PAY = 'pay';
    // Тип операции бонус
    public const TYPE_BONUS = 'bonus';

    private EntityManagerInterface $em;
    private InvoiceRepository $invoiceRepository;
    private TariffsRepository $tariffsRepository;
    private UserRepository $userRepository;
    private SubscriptionHistoryRepository $subscriptionHistoryRepository;

    public function __construct(
        EntityManagerInterface $em,
        InvoiceRepository $invoiceRepository,
        TariffsRepository $tariffsRepository,
        UserRepository $userRepository,
        SubscriptionHistoryRepository $subscriptionHistoryRepository
    ) {
        $this->em = $em;
        $this->invoiceRepository = $invoiceRepository;
        $this->tariffsRepository = $tariffsRepository;
        $this->userRepository = $userRepository;
        $this->subscriptionHistoryRepository = $subscriptionHistoryRepository;
    }

    public function getSubscriptionHistory(User $user)
    {
        $result = [];
        $subscription_history = $this->subscriptionHistoryRepository->findBy(
            ['user' => $user->getId()],
            ['id' => 'desc']
        );

        if ($subscription_history) {
            foreach ($subscription_history as $key => $history) {
                $result[$key] = [
                    'type' => $history->getType(),
                    'description' => $history->getDescription(),
                    'price' => $history->getPrice(),
                    'create_time' => date("Y-m-d H:i", $history->getCreateTime()),
                    'is_active_recurring' => false,
                    'invoice_id' => $history->getInvoice()->getId()
                ];

                if (
                    !$history->getInvoice()->getIsCanceled() &&
                    $history->getInvoice()->getIsRecurring() &&
                    !$history->getInvoice()->getIsAuto()
                ) {
                    $result[$key]['is_active_recurring'] = true;
                }
            }
        }

        return $result;
    }

    /**
     * Сохранение операций подписки
     *
     * @param int $user_id
     * @param string $type
     * @param string $description
     * @param float $price
     * @param int $subscription_from
     * @param int $subscription_to
     *
     * @return [type]
     */
    public function saveHistory(
        User $user,
        string $type,
        string $description,
        float $price
    ): SubscriptionHistory {
        $subscriptionHistory = new SubscriptionHistory();
        $subscriptionHistory
            ->setUser($user)
            ->setPrice($price)
            ->setType($type)
            ->setDescription($description);
        $this->em->persist($subscriptionHistory);
        $this->em->flush();
        return $subscriptionHistory;
    }
}
