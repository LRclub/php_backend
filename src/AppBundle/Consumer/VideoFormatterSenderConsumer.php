<?php

namespace App\AppBundle\Consumer;

use App\Entity\FormattedVideo;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FilesRepository;
use App\Repository\FormattedVideoRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * [Description VideoFormatterSenderConsumer]
 */
class VideoFormatterSenderConsumer implements ConsumerInterface
{
    private EntityManagerInterface $em;
    private FilesRepository $filesRepository;
    private FormattedVideoRepository $formattedVideoRepository;
    private ParameterBagInterface $params;

    public function __construct(
        EntityManagerInterface $em,
        FilesRepository $filesRepository,
        FormattedVideoRepository $formattedVideoRepository,
        ParameterBagInterface $params
    ) {
        $this->em = $em;
        $this->filesRepository = $filesRepository;
        $this->formattedVideoRepository = $formattedVideoRepository;
        $this->params = $params;
    }

    /**
     * @var AMQPMessage $msg
     * @return void
     */
    public function execute(AMQPMessage $msg)
    {
        $result = false;
        $request_data = json_decode($msg->getBody(), true);
        if (!empty($request_data)) {
            $data = [
                'video' =>  new \CURLFILE('../_resources/public/' . $request_data['file_path']),
                'format_to' => $request_data['format_to']
            ];
            $file_entity = $this->filesRepository->find($request_data['file_id']);
            echo "Конвертация видео с ID - " . $file_entity->getId() . PHP_EOL;
            echo "Конвертация в формат - " . $request_data['format_to'] . PHP_EOL;
            $formatted = $this->formattedVideoRepository->findOneBy([
                'file' => $file_entity,
                'type' =>  $request_data['format_to']
            ]);
            if (!$formatted) {
                $formatted = new FormattedVideo();
            }

            $formatted
                ->setFile($file_entity)
                ->setConvertationStatus(1)
                ->setType($data['format_to'])
                ->setStartTime(date('Y-m-d H:i:s'));
            $this->em->persist($formatted);
            $this->em->flush($formatted);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_HTTPHEADER => ["Content-Type" => "multipart/form-data"],
                CURLOPT_URL => $this->params->get('video_formatter.url'),
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $data
            ]);
            $answer = curl_exec($curl);
            $response = json_decode($answer, true);
            if (isset($response['error'])) {
                echo $response['error'] . PHP_EOL;
                $formatted->setConvertationStatus(3);
            }

            if (isset($response['success'])) {
                $formatted
                    ->setFilePath($response['success'])
                    ->setEndTime(date('Y-m-d H:i:s'))
                    ->setConvertationStatus(2);
                $result = true;
                echo "Видео успешно конвертировано." . PHP_EOL;
                echo "Путь к файлу " . $response['success'] . PHP_EOL;
            }

            $this->em->persist($formatted);
            $this->em->flush($formatted);
        }

        if (!$result) {
            echo "Ошибка данных: \n" . $answer . "\n" . PHP_EOL;
        }
    }
}
