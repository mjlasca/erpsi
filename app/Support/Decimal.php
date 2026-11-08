<?php

namespace App\Support;

final class Decimal
{
    public static function round(string $value, int $scale): string
    {
        $negative = str_starts_with($value, '-');
        $absolute = $negative ? substr($value, 1) : $value;
        $increment = $scale === 0 ? '1' : '0.'.str_repeat('0', $scale - 1).'5';

        $rounded = bcadd($absolute, $increment, $scale);

        if ($negative && bccomp($rounded, '0', $scale) !== 0) {
            return '-'.$rounded;
        }

        return $rounded;
    }

    public static function round2(string $value): string
    {
        return self::round($value, 2);
    }

    public static function round3(string $value): string
    {
        return self::round($value, 3);
    }

    public static function maxZero(string $value, int $scale = 2): string
    {
        return bccomp($value, '0', $scale) < 0 ? bcadd('0', '0', $scale) : $value;
    }

    /**
     * @param  iterable<int, string|int|float>  $values
     */
    public static function sum(iterable $values, int $scale = 2): string
    {
        $total = bcadd('0', '0', $scale);

        foreach ($values as $value) {
            $total = bcadd($total, (string) $value, $scale);
        }

        return $total;
    }
}
