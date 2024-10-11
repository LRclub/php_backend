<?php

namespace App\Command;

// Repository
use App\Repository\UserRepository;
use App\Repository\SubscriptionHistoryRepository;
use App\Repository\InvoiceRepository;
// Services
use App\Services\QueueServices;
use App\Services\Payment\PaymentServices;
// Components
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RecurringPaymentCommand extends Command
{
    private ParameterBagInterface $params;
    private QueueServices $queueServices;
    private UserRepository $userRepository;
    private SubscriptionHistoryRepository $subscriptionHistoryRepository;
    private PaymentServices $paymentServices;
    private InvoiceRepository $invoiceRepository;

    protected static $defaultName = 'app:subscription:recurring';

    public function __construct(
        QueueServices $queueServices,
        UserRepository $userRepository,
        ParameterBagInterface $params,
        SubscriptionHistoryRepository $subscriptionHistoryRepository,
        PaymentServices $paymentServices,
        InvoiceRepository $invoiceRepository
    ) {
        $this->userRepository = $userRepository;
        $this->queueServices = $queueServices;
        $this->params = $params;
        $this->subscriptionHistoryRepository = $subscriptionHistoryRepository;
        $this->paymentServices = $paymentServices;
        $this->invoiceRepository = $invoiceRepository;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Повторная оплата подписки');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // Получаем пользователей у которых сегодня заканчивается подписка
        $users = $this->userRepository->findLastDaySubscription();

        if (!$users) {
            $io->success("Подписка всех пользователей актуальна");
            return Command::SUCCESS;
        }

        foreach ($users as $user_info) {
            $user = $user_info[0];
            // Находим последнюю подписку
            $last_subscription = $this->subscriptionHistoryRepository->getLastUserSubscription($user, true);
            if (!$last_subscription) {
                // Если нет подписки, то берем следующего пользователя
                continue;
            }

            $parent_invoice = $last_subscription->getInvoice();
            $last_tariff = $parent_invoice->getTariff();
            if (!$last_tariff) {
                // Если нет тариф не найден, то берем следующего пользователя
                continue;
            }

            // Если пользователь выключил повторение платежей - берем другого
            if ($parent_invoice->getIsCanceled()) {
                continue;
            }

            // Проверяем можем ли мы списать деньги
            if ($this->paymentServices->validateRecurrentPayment($parent_invoice)) {
                // Создаем инвойс
                $invoice = $this->paymentServices->createRecurrentInvoice($user, $last_subscription);

                // Делаем платеж
                $this->paymentServices->createRecurrentPayment($user, $invoice, $parent_invoice);
            }
        }

        $io->success(sprintf('Повторная оплата подписки'));
        return Command::SUCCESS;
    }
}
