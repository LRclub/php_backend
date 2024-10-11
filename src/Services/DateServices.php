<?php

namespace App\Services;

use Symfony\Component\Security\Core\Exception\LogicException;
use DateTime;

/**
 * Валидация дат
 *
 * [Description DateServices]
 */
class DateServices
{
    public function __construct()
    {
    }

    /**
     * Проверка недели
     * $date_from = пн
     * $date_to = вс
     *
     * @param array $date
     *
     * @return [type]
     */
    public function validateWeekRange(array $date)
    {
        if (!isset($date['date_from'])) {
            throw new LogicException("Ошибка корректности параметра");
        }

        if (!isset($date['date_to'])) {
            throw new LogicException("Ошибка корректности параметра");
        }

        // Валидация даты
        $date_from = strtotime($date['date_from']);
        $date_to = strtotime($date['date_to']);
        if (!$date_from || !$date_to) {
            throw new LogicException("Ошибка корректности даты");
        }
        // Проверка 7 дней
        $datediff = $date_to - $date_from;
        $days_diff = floatval($datediff / (60 * 60 * 24));
        if ($days_diff != 6) {
            throw new LogicException("Нужно указать диапазон за неделю");
        }

        if (date('D', $date_from) !== 'Mon') {
            throw new LogicException("Дату начала нужно указать с понедельника");
        }

        if (date('D', $date_to) !== 'Sun') {
            throw new LogicException("Дата окончания должна быть воскресенье");
        }

        return true;
    }

    /**
     * Валидация даты YY
     *
     * @param mixed $date
     *
     * @return [type]
     */
    public function validateYear($date): ?DateTime
    {
        if (!is_numeric($date)) {
            return null;
        }

        if ($date > 2050 || $date < 2000) {
            return null;
        }

        $date = date_create_from_format('Y', $date);
        if (!$date) {
            return null;
        }

        return $date;
    }

    /**
     * Валидация даты YY-MM
     *
     * @param mixed $date
     *
     * @return [type]
     */
    public function validateMonth($date): DateTime
    {
        if (!(bool)preg_match("/^[0-9]{4}-(0[1-9]|1[012])$/", $date)) {
            return false;
        }

        $date = date_create_from_format('Y-m', $date);
        if (!$date) {
            return false;
        }

        return $date;
    }

    /**
     * Проверяем, является ли указанный день воскресеньем
     * Используется для проверки дат в интервалах дневников и т.д.
     *
     * @param $date
     * @return bool
     */
    public function validateDateIsSunday($date): bool
    {
        $date = date_create_from_format('Y-m-d', $date);
        if (!$date) {
            return false;
        }

        return $date->format('N') == 7;
    }

    /**
     * Валидация даты YY-MM-DD
     *
     * @param mixed $date
     *
     * @return [type]
     */
    public function validateDate($date): ?DateTime
    {
        if (!(bool)preg_match("/^[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])$/", $date)) {
            return null;
        }

        $date = date_create_from_format('Y-m-d', $date);
        if (!$date) {
            return null;
        }

        return $date;
    }
}
