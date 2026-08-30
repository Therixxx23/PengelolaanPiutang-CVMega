<?php

namespace App\Support;

class RupiahCompact
{
    public static function format(mixed $value): string
    {
        $value = (float) $value;

        if ($value >= 1_000_000_000) {
            return self::number($value / 1_000_000_000).'m';
        }

        if ($value >= 1_000_000) {
            return self::number($value / 1_000_000).'jt';
        }

        if ($value >= 1_000) {
            return self::number($value / 1_000).'rb';
        }

        return self::number($value);
    }

    private static function number(float $number): string
    {
        $rounded = round($number, 1);

        if (floor($rounded) == $rounded) {
            return number_format($rounded, 0, ',', ',');
        }

        return number_format($rounded, 1, ',', ',');
    }
}
