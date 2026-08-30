<?php

namespace App\Support;

use InvalidArgumentException;

final class Decimal
{
    public static function money(string|int $value): string
    {
        $value = trim((string) $value);
        if (
            ! preg_match('/^-?\d+(?:\.(\d+))?$/', $value, $matches)
            || (isset($matches[1]) && strlen($matches[1]) > 2 && trim(substr($matches[1], 2), '0') !== '')
        ) {
            throw new InvalidArgumentException("Invalid money value [{$value}].");
        }

        return bcadd($value, '0', 2);
    }

    public static function quantity(string|int $value): string
    {
        $value = trim((string) $value);
        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException("Invalid quantity [{$value}].");
        }

        return bcadd($value, '0', 2);
    }

    public static function percentage(string|int $value): string
    {
        $value = trim((string) $value);
        if (! preg_match('/^-?\d+(?:\.\d{1,4})?$/', $value)) {
            throw new InvalidArgumentException("Invalid percentage [{$value}].");
        }

        return bcadd($value, '0', 4);
    }

    public static function add(string|int $left, string|int $right): string
    {
        return bcadd(self::money($left), self::money($right), 2);
    }

    public static function subtract(string|int $left, string|int $right): string
    {
        return bcsub(self::money($left), self::money($right), 2);
    }

    public static function multiply(string|int $amount, string|int $quantity): string
    {
        $raw = bcmul(self::money($amount), self::quantity($quantity), 6);

        return self::roundMoney($raw);
    }

    public static function percentOf(string|int $amount, string|int $percentage): string
    {
        $raw = bcdiv(
            bcmul(self::money($amount), self::percentage($percentage), 8),
            '100',
            8
        );

        return self::roundMoney($raw);
    }

    /** @param iterable<string|int> $values */
    public static function sum(iterable $values): string
    {
        $total = '0.00';
        foreach ($values as $value) {
            $total = self::add($total, $value);
        }

        return $total;
    }

    public static function min(string|int $left, string|int $right): string
    {
        return self::compare($left, $right) <= 0 ? self::money($left) : self::money($right);
    }

    public static function max(string|int $left, string|int $right): string
    {
        return self::compare($left, $right) >= 0 ? self::money($left) : self::money($right);
    }

    public static function compare(string|int $left, string|int $right): int
    {
        return bccomp(self::money($left), self::money($right), 2);
    }

    public static function isPositive(string|int $value): bool
    {
        return self::compare($value, '0.00') > 0;
    }

    public static function isNegative(string|int $value): bool
    {
        return self::compare($value, '0.00') < 0;
    }

    private static function roundMoney(string $value): string
    {
        if (str_starts_with($value, '-')) {
            return bcsub($value, '0.005', 2);
        }

        return bcadd($value, '0.005', 2);
    }
}
