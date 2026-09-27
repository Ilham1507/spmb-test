<?php

namespace App\Support;

use Illuminate\Http\Request;

class Pagination
{
    public const OPTIONS = [10, 20, 50, 100];

    public static function perPage(int $default = 10, ?Request $request = null): int
    {
        $value = (int) (($request ?? request())->query('per_page', $default));

        return in_array($value, self::OPTIONS, true) ? $value : $default;
    }
}
