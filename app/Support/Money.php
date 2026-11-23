<?php

namespace App\Support;

/**
 * Formats amounts in Egyptian pounds with Western digits, e.g. "1,250.50 ج.م".
 */
class Money
{
    public static function format(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2).' ج.م';
    }
}
