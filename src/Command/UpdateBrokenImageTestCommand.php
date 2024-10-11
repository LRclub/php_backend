<?php

namespace App\Command;

// Services
use App\Repository\FilesRepository;
use App\Services\File\FileServices;
// Components
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

class UpdateBrokenImageTestCommand extends Command
{
    private FilesRepository $filesRepository;

    private const PATH_PREFIX = '../_resources/public/';

    protected static $defaultName = 'app:test:fix-images';

    public function __construct(
        FilesRepository $filesRepository
    ) {
        $this->filesRepository = $filesRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Исправляем битые картинки на тестовом сервере');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file_exists = [];
        $file_types = $this->filesRepository->getUniqueFileType();

        foreach ($file_types as $type) {
            $files = $this->filesRepository->getAllFilesByType($type);

            $file_exists[$type] = null;

            foreach ($files as $file) {
                $filepath = realpath(self::PATH_PREFIX . $file);
                if (file_exists($filepath)) {
                    $file_exists[$type] = $filepath;
                    break;
                }
            }
        }

        foreach ($file_types as $type) {
            $files = $this->filesRepository->getAllFilesByType($type);

            if ($file_exists[$type] != null) {
                foreach ($files as $file) {
                    $filepath = self::PATH_PREFIX . $file;
                    if (!file_exists($filepath)) {
                        echo "File not exists $filepath; ";

                        $dirname = pathinfo($filepath, PATHINFO_DIRNAME);
                        if (!file_exists($dirname)) {
                            mkdir($dirname, 0755, true);
                            chown($dirname, 'www-data');
                            chgrp($dirname, 'www-data');
                        }
                        $full_path_filename =
                            realpath($dirname) . DIRECTORY_SEPARATOR . pathinfo($filepath, PATHINFO_BASENAME);

                        $status = symlink($file_exists[$type], $full_path_filename);

                        echo "Creating symlink: " . ($status ? 'SUCCESS' : 'FAILED') . "\r\n";
                    }
                }
            }
        }

        return Command::SUCCESS;
    }
}
