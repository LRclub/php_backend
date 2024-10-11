<?php

namespace App\Services;

class HelperServices
{
    /**
     * @param mixed $string
     *
     * @return [string]
     */
    public static function transliteration(string $string): string
    {
        $converter = array(
            'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
            'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
            'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
            'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
            'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
            'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
            'э' => 'e',    'ю' => 'yu',   'я' => 'ya',
        );

        $slug = mb_strtolower($string);
        // Убираем все кроме букв
        #$slug = preg_replace('/\PL/u', '', $slug);

        $slug = strtr($slug, $converter);
        $slug = str_replace(' ', '-', $slug);
        $slug = mb_ereg_replace('[^-a-z]', '', $slug);
        $slug = mb_ereg_replace('[-]+', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }

    /**
     * Возвращаем строку в отформатированном виде, без лишних пробелов
     *
     * @param string $text
     * @return string
     */
    public static function prepareText(string $text): string
    {
        $text = preg_replace("/(\r?\n){2,}/", "\n\n", $text);

        return trim($text);
    }

    /**
     * Обрезаем текст до максимальной длины
     *
     * @param $text
     * @param int $length
     * @return string
     */
    public static function cutText($text, $length = 50): string
    {
        if (strlen($text) > $length) {
            $text = mb_substr($text, 0, $length - 3) . '...';
        }

        return $text;
    }

    /**
     * Проверка на JSON
     * @param $string
     * @return bool
     */
    public static function isJson($string): bool
    {
        return ((is_string($string) &&
            (is_object(json_decode($string)) ||
                is_array(json_decode($string))))) ? true : false;
    }
}
