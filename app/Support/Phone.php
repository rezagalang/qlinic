<?php

namespace App\Support;

class Phone
{
    /** Normalisasi ke format lokal: 08xxxxxxxxxx (menerima +62, 62, 8, spasi, strip). */
    public static function normalize(string $input): string
    {
        $digits = preg_replace('/\D+/', '', $input) ?? '';

        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public static function isValid(string $normalized): bool
    {
        return (bool) preg_match('/^08\d{8,12}$/', $normalized);
    }
}
