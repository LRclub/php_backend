<?php

namespace App\Command;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Psr\Container\ContainerInterface;

class MailSendProducerCommand extends Command
{
    /**
     * @var Container
     */
    private $container;

    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('app:rabbit-send-message');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container->get('old_sound_rabbit_mq.lrclub_send_email_producer')->publish('время ' . date('H:i:s'));

        return Command::SUCCESS;
    }
}
