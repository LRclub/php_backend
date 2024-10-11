<?php

namespace App\Services\Admin;

// Repository
use App\Repository\SubscriptionHistoryRepository;
// Etc
use Doctrine\ORM\EntityManagerInterface;

class AdminPaymentServices
{
    private const PAGE_OFFSET = 20;

    private SubscriptionHistoryRepository $subscriptionHistoryRepository;

    public function __construct(
        SubscriptionHistoryRepository $subscriptionHistoryRepository
    ) {
        $this->subscriptionHistoryRepository = $subscriptionHistoryRepository;
    }

    /**
     * Список оплат
     *
     * @param int $page
     * @param string $search
     * @param array $order_by
     *
     * @return [type]
     */
    public function getPaymentHistory(
        int $page,
        string $search,
        array $order_by
    ) {
        $result = [];
        $limit = self::PAGE_OFFSET;
        $offset = (intval($page - 1)) * self::PAGE_OFFSET;
        $result = ['pages' => 0, 'payment' => [], 'payments_count' => 0];

        $subscription_history = $this->subscriptionHistoryRepository->getAdminPaymentHistory(
            $limit,
            $offset,
            $order_by,
            $search
        );

        if (!$subscription_history) {
            return $result;
        }

        foreach ($subscription_history as $history) {
            $user = $history->getUser();
            $result['payment'][] = [
                'id' => $history->getId(),
                'type' => $history->getType(),
                'description' => $history->getDescription(),
                'price' => $history->getPrice(),
                'create_time' => date("d.m.Y H:i", $history->getCreateTime()),
                'user_id' => $user->getId(),
                'fio' => $user->getFirstName() . " " . $user->getLastName() . " " . $user->getPatronymicName(),
            ];
        }

        $result['payments_count'] = (int)$this->subscriptionHistoryRepository->getAdminPaymentHistory(
            $limit,
            $offset,
            $order_by,
            $search,
            true
        );

        $result['pages'] = round($result['payments_count'] / self::PAGE_OFFSET);

        return $result;
    }
}
