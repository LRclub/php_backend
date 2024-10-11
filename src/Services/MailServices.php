<?php

namespace App\Services;

use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailServices
{
    private MailerInterface $mailer;
    private ParameterBagInterface $params;

    public function __construct(
        MailerInterface $mailer,
        ParameterBagInterface $params
    ) {
        $this->mailer = $mailer;
        $this->params = $params;
    }

    /**
     * Отправка писем
     *
     * @param $to
     * @param $subject
     * @param $template_file
     * @param array $template_data
     * @param array $attachments
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendTemplateEmail($to, $subject, $template_file, array $template_data = [], array $attachments = [])
    {
        $template_data = array_merge($template_data, ['system' => $this->params->all()]);
        $subject = $subject . ' - ' . $this->params->get('base.name');
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->params->get('mail.from_email'), $this->params->get('mail.from_name')))
                ->to($to)
                ->subject($subject)
                ->htmlTemplate($template_file)
                ->context($template_data);
            $this->mailer->send($email);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
