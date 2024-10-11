<?php

namespace App\Command;

// Repository
use App\Entity\User;
use App\Repository\NoticeRepository;
use App\Repository\UserRepository;
// Services
use App\Services\QueueServices;
use App\Services\Notice\NoticeServices;
// Components
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SubscriptionNotificationCommand extends Command
{
    private ParameterBagInterface $params;
    private QueueServices $queueServices;
    private UserRepository $userRepository;
    private NoticeServices $noticeServices;

    protected static $defaultName = 'app:subscription:notification';

    public function __construct(
        QueueServices $queueServices,
        UserRepository $userRepository,
        ParameterBagInterface $params,
        NoticeServices $noticeServices
    ) {
        $this->userRepository = $userRepository;
        $this->queueServices = $queueServices;
        $this->params = $params;
        $this->noticeServices = $noticeServices;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Уведомление об окончании подписки');
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
        $users = $this->userRepository->findEndingSubscription();
        $days = null;
        if (!$users) {
            $io->success("Ничего не найдено");
            return Command::SUCCESS;
        }

        foreach ($users as $user_info) {
            $user = $user_info[0];
            $end_days = (int)$user_info['date_end'];
            switch ($end_days) {
                case 7:
                    $days = '7 дней';
                    break;
                case 3:
                    $days = '3 дня';
                    break;
                case 1:
                    $days = '1 день';
                    break;
            }

            $payment_link = $this->params->get('base.url') . '/panel/payment';
            // Отправка email
            if (
                $user->getNotifications()
                && $user->getNotifications()->getSubscriptionHistory()
                && $user->getIsConfirmed()
            ) {
                $subject = 'Подписка завершится через ' . $days;
                $this->queueServices->sendEmail(
                    $user->getEmail(),
                    $subject,
                    '/mail/user/notifications/payment_end.html.twig',
                    [
                        'days' => $days,
                        'payment_link' => $payment_link
                    ]
                );
            }

            // Создание notice
            $this->noticeServices->createNoticeByArrayData($user, [
                'type' => NoticeRepository::TYPE_INFO,
                'message' => "Ваша подписка скоро закончится. Оставшийся срок $days",
                'category' => NoticeRepository::CATEGORY_PAYMENT,
                'data' => ['link' => $payment_link]
            ]);
        }

        $io->success(sprintf('Уведомления пользователям об окончании подписки отправлены'));
        return Command::SUCCESS;
    }
}
