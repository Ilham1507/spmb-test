<?php

namespace App\Support;

class ParticipantNameFormatter
{
    /** Format a participant name consistently without changing its meaning. */
    public static function titleCase(?string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim((string) $name)) ?? trim((string) $name);

        return mb_convert_case(mb_strtolower($name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
