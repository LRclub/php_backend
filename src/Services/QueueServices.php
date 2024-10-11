<?php

namespace App\Services;

use App\Entity\Files;
use Psr\Container\ContainerInterface;

class QueueServices
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
    }

    /**
     * Отправка email писем
     *
     * @param $to
     * @param $subject
     * @param $template_file
     * @param array $template_data
     * @param array $attachments
     */
    public function sendEmail($to, $subject, $template_file, array $template_data = [], array $attachments = [])
    {
        $data = [
            'to' => $to,
            'subject' => $subject,
            'template_file' => $template_file,
            'template_data' => $template_data,
            'attachments' => $attachments,
        ];

        $this->container->get('old_sound_rabbit_mq.lrclub_send_email_producer')->publish(json_encode($data));
    }

    /**
     * Отправка sms сообщений
     *
     * @param mixed $phone
     * @param mixed $message
     *
     * @return [type]
     */
    public function sendSMS($phone, $message)
    {
        $data = [
            'phone' => $phone,
            'message' => $message,
        ];

        $this->container->get('old_sound_rabbit_mq.lrclub_send_sms_producer')->publish(json_encode($data));

        return true;
    }

    /**
     * Отправка сообщений в чат
     *
     * @param mixed $message
     *
     * @return [type]
     */
    public function sendChat($action, $message_id)
    {
        $data = [
            'action' => $action,
            'message_id' => $message_id
        ];

        $this->container->get('old_sound_rabbit_mq.lrclub_send_chat_producer')->publish(json_encode($data));

        return true;
    }

    /**
     * Отправка комментариев, удаление, редактирование, лайк
     *
     * @param mixed $message
     *
     * @return [type]
     */
    public function sendComments($action, $comment_id)
    {
        $data = [
            'action' => $action,
            'comment_id' => $comment_id
        ];

        $this->container->get('old_sound_rabbit_mq.lrclub_send_comments_producer')->publish(json_encode($data));

        return true;
    }

    /**
     * Отправка событий лайк/кол-во комментариев для материала
     *
     * @param mixed $action
     * @param mixed $data
     *
     * @return [type]
     */
    public function sendEvents($action, $material_id)
    {
        $data = [
            'action' => $action,
            'material_id' => $material_id
        ];

        $this->container->get('old_sound_rabbit_mq.lrclub_send_events_producer')->publish(json_encode($data));

        return true;
    }

    /**
     * @param mixed $action
     * @param mixed $message_id
     *
     * @return [type]
     */
    public function sendFeedbackMessage($action, $message_id)
    {
        $data = [
            'action' => $action,
            'message_id' => $message_id
        ];

        $this->container->get('old_sound_rabbit_mq.lrclub_send_feedback_producer')->publish(json_encode($data));

        return true;
    }

    /**
     * Формирование данных для рэббита. Проверка на необходимость конвертации.
     *
     * @param mixed $ext
     * @param mixed $path
     *
     * @return [type]
     */
    public function videoFormatting($ext, Files $file_entity)
    {
        $data = [
            'ext' => $ext,
            'file_path' => $file_entity->getFilePath(),
            'file_id' =>  $file_entity->getId(),
            'format_to' => null
        ];

        if ($ext != 'mp4') {
            $data['format_to'] = 'mp4';
            $this->startConvertation($data);
        }

        if ($ext != 'webm') {
            $data['format_to'] = 'webm';
            $this->startConvertation($data);
        }

        return true;
    }

    /**
     * Запуск рэббита на начало конвертации видео
     *
     * @param array $data
     *
     * @return [type]
     */
    private function startConvertation(array $data)
    {
        $this->container->get('old_sound_rabbit_mq.lrclub_video_formatter_producer')->publish(json_encode($data));
    }
}
