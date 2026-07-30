<?php

namespace App\Support;

final class PhoneNormalizer
{
    /**
     * Store phone numbers as digits only. Polish local numbers receive country
     * code 48, so 600 000 000, +48 600 000 000 and 0048 600 000 000 match.
     */
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 9) {
            $digits = '48'.$digits;
        }

        return $digits;
    }
}
