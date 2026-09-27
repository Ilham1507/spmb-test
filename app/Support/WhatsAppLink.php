<?php

namespace App\Support;

class WhatsAppLink
{
    public static function make(?string $phone, string $message): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $phone);

        if ($number === '') {
            return null;
        }

        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        } elseif (! str_starts_with($number, '62')) {
            $number = '62'.$number;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}
