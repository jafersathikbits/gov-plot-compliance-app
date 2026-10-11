<?php

namespace App\Support;

class Format
{
    /**
     * Format a number with Indian digit grouping, e.g. 12,34,567.50.
     */
    public static function inr(mixed $value, bool $symbol = true): string
    {
        $value = (float) $value;
        $negative = $value < 0;
        [$whole, $fraction] = explode('.', number_format(abs($value), 2, '.', ''));

        if (strlen($whole) > 3) {
            $last3 = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $whole = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).','.$last3;
        }

        $formatted = $fraction === '00' ? $whole : $whole.'.'.$fraction;

        return ($negative ? '-' : '').($symbol ? '₹' : '').$formatted;
    }

    public static function number(mixed $value): string
    {
        $value = (float) $value;

        return floor($value) == $value ? number_format($value) : number_format($value, 2);
    }

    public static function label(?string $value): string
    {
        return ucfirst(str_replace('_', ' ', (string) $value));
    }
}
