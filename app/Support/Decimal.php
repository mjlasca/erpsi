<?php

namespace App\Support;

use InvalidArgumentException;

final class Decimal
{
    /**
     * @return numeric-string
     */
    public static function add(string $left, string $right, int $scale = 2): string
    {
        return self::ensure(bcadd(self::numeric($left), self::numeric($right), $scale));
    }

    /**
     * @return numeric-string
     */
    public static function sub(string $left, string $right, int $scale = 2): string
    {
        return self::ensure(bcsub(self::numeric($left), self::numeric($right), $scale));
    }

    /**
     * @return numeric-string
     */
    public static function multiply(string $left, string $right, int $scale = 4): string
    {
        return self::ensure(bcmul(self::numeric($left), self::numeric($right), $scale));
    }

    /**
     * @return numeric-string
     */
    public static function divide(string $left, string $right, int $scale = 8): string
    {
        return self::ensure(bcdiv(self::numeric($left), self::numeric($right), $scale));
    }

    public static function compare(string $left, string $right, int $scale = 2): int
    {
        return bccomp(self::numeric($left), self::numeric($right), $scale);
    }

    public static function round(string $value, int $scale): string
    {
        $negative = str_starts_with($value, '-');
        $absolute = $negative ? substr($value, 1) : $value;
        $increment = $scale === 0 ? '1' : '0.'.str_repeat('0', $scale).'5';

        $rounded = self::add($absolute, $increment, $scale);

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
        return self::compare($value, '0', $scale) < 0 ? self::add('0', '0', $scale) : $value;
    }

    /**
     * @param  iterable<int, string|int|float>  $values
     */
    public static function sum(iterable $values, int $scale = 2): string
    {
        $total = bcadd('0', '0', $scale);

        foreach ($values as $value) {
            $total = self::add($total, (string) $value, $scale);
        }

        return $total;
    }

    /**
     * @return numeric-string
     */
    private static function numeric(string $value): string
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("El valor [{$value}] no es numérico.");
        }

        return $value;
    }

    /**
     * @return numeric-string
     */
    private static function ensure(string $value): string
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("El resultado [{$value}] no es numérico.");
        }

        return $value;
    }
}
