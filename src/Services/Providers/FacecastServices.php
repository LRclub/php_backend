<?php

namespace App\Services\Providers;

use App\Entity\Materials;
use App\Entity\User;
use App\Interfaces\PaymentProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Services\TwigServices;

class FacecastServices
{
    private const URL = 'https://facecast.net/api/v1';

    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * Добавление ключа пользователю для эфира
     *
     * @param User $user
     * @param Materials $material
     * @param int $event_id
     *
     * @return [type]
     */
    public function addUserForStream(
        User $user,
        Materials $material,
        string $code
    ) {
        if (!$material->getStream()) {
            return false;
        }

        $event_id = $this->getEventIdByUrl($material->getStream());
        if (!$event_id) {
            return false;
        }

        $event_id = $this->getEventIdByUrl($material->getStream());
        if (!$event_id) {
            return false;
        }

        $array = [
            'uid'    => $this->params->get('facecast.uid'),
            'api_key' => $this->params->get('facecast.api_key'),
            'event_id' => $event_id,
            'key' => $code,
            'email' => $user->getEmail(),
            'name' => $user->getFirstName(),
            'phone' => (int)$user->getPhone(),
            'multiple_vpp' => 0
        ];

        $ch = curl_init(self::URL . '/insert_key');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array, '', '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $data = json_decode(curl_exec($ch));
        curl_close($ch);
        if (isset($data->error)) {
            return false;
        }

        return $data;
    }

    /**
     * Поиск event_id по URL
     *
     * @param string $url
     *
     * @return [type]
     */
    public function getEventIdByUrl(string $url)
    {
        $streams = $this->getActiveStreams();
        if (!$streams) {
            return false;
        }

        $path = parse_url($url)['path'];
        if (empty($path)) {
            return false;
        }

        foreach ($streams as $stream) {
            if ($path == '/w/' . $stream->code) {
                return $stream->id;
            }
        }

        return false;
    }

    /**
     * Получение активных стримов
     *
     * @return [type]
     */
    public function getActiveStreams()
    {
        $channel_id = $this->getChannelId();
        if (!$channel_id) {
            return false;
        }

        $array = [
            'uid'    => $this->params->get('facecast.uid'),
            'api_key' => $this->params->get('facecast.api_key'),
            'channel_id' => $channel_id
        ];

        $ch = curl_init(self::URL . '/get_events_by_channel');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array, '', '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $data = json_decode(curl_exec($ch));
        curl_close($ch);

        return $data;
    }

    /**
     * Получение ID канала по названию из env параметра
     *
     * @return [type]
     */
    public function getChannelId()
    {
        $array = [
            'uid' => $this->params->get('facecast.uid'),
            'api_key' => $this->params->get('facecast.api_key'),
        ];

        $ch = curl_init(self::URL . '/get_channels');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array, '', '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $data = json_decode(curl_exec($ch));
        curl_close($ch);

        foreach ($data as $value) {
            if (isset($value->code) && $value->code == $this->params->get('facecast.channel_name')) {
                return $value->id;
            }
        }

        return false;
    }
}
