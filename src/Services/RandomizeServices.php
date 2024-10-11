<?php

namespace App\Services;

class RandomizeServices
{
    /**
     * Randomize string generator
     * @param int $length
     * @return string
     */
    public static function generateString(
        $length = 10,
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ) {
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
