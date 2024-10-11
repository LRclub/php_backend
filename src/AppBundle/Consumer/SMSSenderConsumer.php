<?php

namespace App\AppBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use App\Services\SMSServices;
use Exception;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

/**
 * [Description SMSSenderConsumer]
 */
class SMSSenderConsumer implements ConsumerInterface
{
    private ProducerInterface $delayedProducer;
    private SMSServices $SMSServices;

    public function __construct(
        ProducerInterface $delayedProducer,
        SMSServices $SMSServices
    ) {
        $this->delayedProducer = $delayedProducer;
        $this->SMSServices = $SMSServices;
        gc_enable();
    }

    /**
     * @var AMQPMessage $msg
     * @return void
     */
    public function execute(AMQPMessage $msg)
    {
        $result = true;
        $sms = json_decode($msg->getBody(), true);
        if (!empty($sms)) {
            try {
                $result = $this->SMSServices->sendSMS($sms['phone'], $sms['message']);
                if ($result) {
                    echo "Сообщение успешно отправлено на номер - " . $sms['phone']  .
                        " Текст сообщения: " . $sms['message'] . PHP_EOL;
                }
            } catch (Exception $e) {
                echo "Ошибка отправки сообщения на номер " . $sms['phone']  .
                    " Текст сообщения: " . $sms['message'] . " Текст ошибки: " . $e->getMessage() . PHP_EOL;
                $result = false;
            }
        }

        if (!$result) {
            echo "Ошибка отправки сообщения.  В ответе API не пришло success." .
                " Номер отправки - " . $sms['phone'] . " Текст сообщения: " . $sms['message'] . PHP_EOL;
            $this->delayedProducer->publish($msg->getBody());
        }

        gc_collect_cycles();
    }
}
