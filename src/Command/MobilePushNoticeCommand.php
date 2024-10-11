<?php

namespace App\Command;

// Services
use App\Services\Materials\MaterialsServices;
// Components
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NoticeRepository;
use App\Services\MobileClient\MobileClientServices;
use App\Repository\MobileClientIdRepository;

class MobilePushNoticeCommand extends Command
{
    private NoticeRepository $noticeRepository;
    private MobileClientServices $mobileClientServices;
    private MobileClientIdRepository $mobileClientIdRepository;
    private EntityManagerInterface $em;

    protected static $defaultName = 'app:mobile:push';

    public function __construct(
        NoticeRepository $noticeRepository,
        MobileClientServices $mobileClientServices,
        MobileClientIdRepository $mobileClientIdRepository,
        EntityManagerInterface $em
    ) {
        $this->noticeRepository = $noticeRepository;
        $this->mobileClientServices = $mobileClientServices;
        $this->mobileClientIdRepository = $mobileClientIdRepository;
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Отправить PUSH уведомления');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = [];
        $notices = $this->noticeRepository->findBy(['is_push_sended' => 0]);

        if (!$notices) {
            $io->success("Уведомления для push не найдены");
            return Command::SUCCESS;
        }

        // Группируем - категория - ссылка
        foreach ($notices as $notice) {
            // Проверяем есть ли у пользователя client_ids
            $client_ids = $this->mobileClientIdRepository->findBy(['user' => $notice->getUser()]);
            $user_client_ids_array = [];
            if ($client_ids) {
                foreach ($client_ids as $client_id) {
                    array_push($user_client_ids_array, $client_id->getClientId());
                }

                if (!empty($notice->getData()['link'])) {
                    $result[] = [
                        'link' => $notice->getData()['link'],
                        'title' => $notice->getCategoryTitle(),
                        'message' => $notice->getMessage(),
                        'user_id' => $notice->getUser()->getId(),
                        'client_ids' => $user_client_ids_array
                    ];
                }
            }
        }

        if (!empty($result)) {
            $this->mobileClientServices->sendPush($result);
        }

        foreach ($notices as $notice) {
            $notice->setIsPushSended(true);
            $this->em->persist($notice);
        }

        $this->em->flush();

        $io->success(sprintf('Push уведомления отправлены'));

        return Command::SUCCESS;
    }
}
