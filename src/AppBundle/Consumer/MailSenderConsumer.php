<?php

namespace App\AppBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use App\Services\MailServices;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

/**
 * [Description MailSenderConsumer]
 */
class MailSenderConsumer implements ConsumerInterface
{
    private $delayedProducer;
    private MailServices $mailServices;

    /**
     * MailSenderConsumer constructor.
     * @param ProducerInterface      $delayedProducer
     */
    public function __construct(
        ProducerInterface $delayedProducer,
        MailServices $mailServices
    ) {
        $this->delayedProducer = $delayedProducer;
        $this->mailServices = $mailServices;
        gc_enable();
    }

    /**
     * @var AMQPMessage $msg
     * @return void
     */
    public function execute(AMQPMessage $msg)
    {
        $result = true;
        $email = json_decode($msg->getBody(), true);
        if (
            !empty($email) &&
            isset($email['to']) &&
            isset($email['subject']) &&
            isset($email['template_file'])
        ) {
            $result = $this->mailServices->sendTemplateEmail(
                $email['to'],
                $email['subject'],
                $email['template_file'],
                $email['template_data'] ?? [],
                $email['attachments'] ?? []
            );
            if ($result) {
                echo 'Письмо для ' . $email['to'] . ' отправлено с темой "' . $email['subject'] . '"' . PHP_EOL;
            }
        }

        if (!$result) {
            echo 'Ошибка отправки письма для ' . $email['to'] . ' с темой "' . $email['subject'] . '"'  . PHP_EOL;
            $this->delayedProducer->publish($msg->getBody());
        }

        gc_collect_cycles();
    }
}
