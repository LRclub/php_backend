<?php

namespace App\Services;

use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\OperatingSystem;

class SystemServices
{
    private const DEVICE_TYPE_APP = 'app';
    private const DEVICE_TYPE_WEBSITE = 'website';

    public function __construct()
    {
    }

    /**
     * Информации о пользователе
     *
     * @return [type]
     */
    public function getUserDeviceInfo()
    {
        // User Agent и Device Type
        $platform = self::DEVICE_TYPE_WEBSITE;
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $dd = new DeviceDetector($user_agent);
        $dd->parse();
        $device_type = $dd->getDeviceName();
        $device_os = OperatingSystem::getOsFamily($dd->getOs('name'));
        if (str_contains($user_agent, 'Mobile App')) {
            $platform = self::DEVICE_TYPE_APP;
            $device_type = self::DEVICE_TYPE_APP;
            ;
        }

        return [
            'user_agent' => $user_agent,
            'device_type' => $device_type,
            'device_os' => $device_os,
            'platform' => $platform
        ];
    }
}
