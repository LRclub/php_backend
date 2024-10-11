<?php

namespace App\Services\MobileClient;

use App\Entity\User;
use App\Entity\MobileClientId;
use App\Entity\Notice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\MobileClientIdRepository;
use Symfony\Component\Security\Core\Exception\LogicException;

class MobileClientServices
{
    private EntityManagerInterface $em;
    private MobileClientIdRepository $mobileClientIdRepository;
    private ParameterBagInterface $params;

    public function __construct(
        EntityManagerInterface $em,
        MobileClientIdRepository $mobileClientIdRepository,
        ParameterBagInterface $params
    ) {
        $this->em = $em;
        $this->mobileClientIdRepository = $mobileClientIdRepository;
        $this->params = $params;
    }

    /**
     * @param User $user
     * @param int $client_id
     *
     * @return [type]
     */
    public function saveMobileClientId(User $user, string $client_id)
    {
        if (empty($client_id)) {
            throw new LogicException("Нужно указать ID client");
        }

        if (mb_strlen($client_id) > 25 || mb_strlen($client_id) < 15) {
            throw new LogicException("Нужно указать корректный сlient ID");
        }

        if (!is_numeric($client_id)) {
            throw new LogicException("Нужно указать корректный сlient ID");
        }

        $exist_client = $this->mobileClientIdRepository->findOneBy(['client_id' => $client_id]);

        if ($exist_client) {
            // Если client_id есть и связан с другим пользователем
            if ($exist_client->getUser() != $user) {
                throw new LogicException("Не удалось сохранить client_id");
            }

            // Если client_id уже подвязан к этому пользователю
            if ($exist_client->getUser() == $user) {
                return $exist_client;
            }
        }

        $mobile_client = new MobileClientId();
        $mobile_client->setUser($user)->setClientId($client_id)->setCreateTime(time());
        $this->em->persist($mobile_client);
        $this->em->flush();

        return $mobile_client;
    }

    /**
     * Отправка уведомления на телефон
     *
     * @param array $data
     *
     * @return [type]
     */
    public function sendPush(array $data)
    {
        $token = $this->params->get('app.metrika.token');
        $group_id = $this->params->get('app.metrika.group_id');

        $authorization_header = ["Authorization: OAuth $token", "Content-Type: application/json"];
        $client_transfer_id = time();
        $messages = [];

        foreach ($data as $message) {
            $title = $message['title'];
            $text = $message['message'];
            $link = $message['link'];
            $id_values = $message['client_ids'];

            $messages[] = [
                'messages' => [
                    'android' => [
                        'silent' => false,
                        'content' => [
                            "title" => $title,
                            "text" => $text,
                        ],
                        "open_action" => [
                            "deeplink" => "$link"
                        ]
                    ]
                ],
                'devices' => [[
                    'id_type' => "appmetrica_device_id",
                    'id_values' => $id_values
                ]]
            ];
        }

        $request_data = [
            "push_batch_request" => [
                'group_id' => $group_id,
                'client_transfer_id' => $client_transfer_id,
                'tag' => 'string',
                'batch' => $messages
            ]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://push.api.appmetrica.yandex.net/push/v1/send-batch',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($request_data),
            CURLOPT_HTTPHEADER => $authorization_header,
        ));

        $response = curl_exec($curl);

        // TODO удалить
        file_put_contents('log.txt', json_encode($request_data, JSON_UNESCAPED_UNICODE) . "\r\n", FILE_APPEND);
        file_put_contents('log.txt', $response . "\r\n\r\n", FILE_APPEND);

        curl_close($curl);
    }
}
