<?php

namespace App\Command;

// Repository
use App\Entity\User;
use App\Repository\NoticeRepository;
use App\Repository\UserRepository;
use App\Repository\MaterialsRepository;
// Services
use App\Services\QueueServices;
use App\Services\Notice\NoticeServices;
// Components
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\MaterialsNotificationEvent;

class MaterialsNotificationCommand extends Command
{
    private ParameterBagInterface $params;
    private QueueServices $queueServices;
    private UserRepository $userRepository;
    private NoticeServices $noticeServices;
    private MaterialsRepository $materialsRepository;
    private EventDispatcherInterface $eventDispatcher;

    protected static $defaultName = 'app:materials:notification';

    public function __construct(
        QueueServices $queueServices,
        UserRepository $userRepository,
        ParameterBagInterface $params,
        NoticeServices $noticeServices,
        MaterialsRepository $materialsRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->userRepository = $userRepository;
        $this->queueServices = $queueServices;
        $this->params = $params;
        $this->noticeServices = $noticeServices;
        $this->materialsRepository = $materialsRepository;
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Уведомление пользователей о материале');
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
        $materials = $this->materialsRepository->getLazyPublishedMaterials();
        if (!$materials) {
            $io->success("Ничего не найдено");
            return Command::SUCCESS;
        }

        foreach ($materials as $material) {
            $this->eventDispatcher->dispatch(
                new MaterialsNotificationEvent($material),
                MaterialsNotificationEvent::NOTIFICATION_MATERIAL_NEW
            );
        }

        $io->success(sprintf('Уведомления пользователям о новом материале отправлено'));
        return Command::SUCCESS;
    }
}
