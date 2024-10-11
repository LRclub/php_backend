<?php

namespace App\Services\Payment;

// Entity

use App\Entity\Tariffs;
use App\Entity\User;
// Repository
use App\Repository\InvoiceRepository;
use App\Repository\TariffsRepository;
use App\Repository\UserRepository;
use App\Repository\SubscriptionHistoryRepository;
use App\Repository\PromocodesUsedRepository;
// Services
use App\Services\Marketing\PromocodeServices;
use App\Services\Payment\SubscriptionHistoryServices;
// Etc
use Doctrine\ORM\EntityManagerInterface;

class TariffsServices
{
    private EntityManagerInterface $em;
    private InvoiceRepository $invoiceRepository;
    private TariffsRepository $tariffsRepository;
    private SubscriptionHistoryRepository $subscriptionHistoryRepository;
    private UserRepository $userRepository;
    private PromocodesUsedRepository $promocodesUsedRepository;

    public function __construct(
        EntityManagerInterface $em,
        InvoiceRepository $invoiceRepository,
        TariffsRepository $tariffsRepository,
        SubscriptionHistoryRepository $subscriptionHistoryRepository,
        UserRepository $userRepository,
        PromocodesUsedRepository $promocodesUsedRepository
    ) {
        $this->em = $em;
        $this->invoiceRepository = $invoiceRepository;
        $this->tariffsRepository = $tariffsRepository;
        $this->subscriptionHistoryRepository = $subscriptionHistoryRepository;
        $this->userRepository = $userRepository;
        $this->promocodesUsedRepository = $promocodesUsedRepository;
    }

    /**
     * Получение списка активных тарифов
     *
     * @return [type]
     */
    public function getTariffs(User $user)
    {
        $result = [];

        // Если у пользователя есть подписка
        if (!empty($user->getSubscriptionEndDate()) && $user->getSubscriptionEndDate() > time()) {
            // Смотрим последнюю подписку
            $last_subscription = $this->subscriptionHistoryRepository->getLastUserSubscription($user);
            if ($last_subscription) {
                // Получаем тариф
                $tariff = $last_subscription->getInvoice()->getTariff();
                // Проверяем актуальность. Если не активен, то получаем по версии
                if (!$tariff->getIsActive()) {
                    $tariffs = $this->tariffsRepository->getTariffVersion($tariff->getType(), $tariff->getVersion());
                    if ($tariffs) {
                        foreach ($tariffs as $tariff) {
                            $result[] = $this->getTariffData($tariff);
                        }
                    }

                    $tariffs = $this->tariffsRepository->getOtherActiveTariffs($tariff->getType());
                    if ($tariffs) {
                        foreach ($tariffs as $tariff) {
                            $result[] = $this->getTariffData($tariff);
                        }
                    }

                    return $result;
                }
            }
        }

        $tariffs = $this->tariffsRepository->findBy(['is_active' => true]);
        if (!$tariffs) {
            return [];
        }

        foreach ($tariffs as $tariff) {
            $result[] = $this->getTariffData($tariff);
        }

        return $result;
    }

    /**
     * @param Tariffs $tariff
     *
     * @return [type]
     */
    private function getTariffData(Tariffs $tariff)
    {
        $result = [
            'id' => $tariff->getId(),
            'number_months' => $tariff->getNumberMonths(),
            'price' => $tariff->getPrice(),
            'type' => $tariff->getType()
        ];

        return $result;
    }
}
