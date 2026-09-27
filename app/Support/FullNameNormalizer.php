<?php

namespace App\Support;

class FullNameNormalizer
{
    public static function normalize(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name), 'UTF-8');

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }
}
