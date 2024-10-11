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

class UpdateStreamStatusCommand extends Command
{
    private MaterialsServices $materialsServices;

    protected static $defaultName = 'app:stream:update';

    public function __construct(
        MaterialsServices $materialsServices
    ) {
        $this->materialsServices = $materialsServices;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Закрыть открытые эфиры');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->materialsServices->updateStreamsStatus();
        if (!$result) {
            $io->success("Активных эфиров не найдено");
            return Command::SUCCESS;
        }

        $io->success(sprintf('Эфиры успешно закрыты'));
        return Command::SUCCESS;
    }
}
