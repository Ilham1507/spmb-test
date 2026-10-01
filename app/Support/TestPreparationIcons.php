<?php

namespace App\Support;

class TestPreparationIcons
{
    public const OPTIONS = [
        'phone' => 'HP / internet', 'shirt' => 'Pakaian', 'family' => 'Orang tua / wali',
        'document' => 'Dokumen', 'pen' => 'Alat tulis', 'clock' => 'Waktu',
        'location' => 'Lokasi', 'info' => 'Informasi',
    ];

    public static function legacy(int $index): string
    {
        return match ($index) { 0 => 'phone', 1 => 'shirt', 2 => 'family', default => 'document' };
    }

    public static function resolve(?string $icon, int $index): string
    {
        return isset(self::OPTIONS[$icon ?? '']) ? $icon : self::legacy($index);
    }
}
