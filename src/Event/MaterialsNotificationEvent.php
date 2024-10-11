<?php

namespace App\Event;

use App\Entity\Materials;
use Symfony\Contracts\EventDispatcher\Event;

class MaterialsNotificationEvent extends Event
{
    // Уведомление пользователя о новом материале
    public const NOTIFICATION_MATERIAL_NEW = 'notification.new_material';
    public const NOTIFICATION_STREAM_START = 'notification.stream_start';

    protected $material;

    public function __construct(Materials $material)
    {
        $this->material = $material;
    }

    public function getMaterial()
    {
        return $this->material;
    }
}
