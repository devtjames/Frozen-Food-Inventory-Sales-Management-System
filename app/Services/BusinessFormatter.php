<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class BusinessFormatter
{
    public function __construct(private readonly BusinessSettingsService $settings) {}

    public function money(string|int $amount, ?string $currencyCode = null): string
    {
        $currencyCode ??= $this->settings->get()->currency_code;
        [$negative, $whole, $fraction] = $this->decimalParts($amount, 2);
        $symbol = $this->settings->currencySymbol($currencyCode);

        return ($negative ? '-' : '').$symbol.$this->groupDigits($whole).'.'.$fraction;
    }

    public function kilograms(string|int $quantity): string
    {
        [$negative, $whole, $fraction] = $this->decimalParts($quantity, 3);
        $fraction = rtrim($fraction, '0');

        return ($negative ? '-' : '').$this->groupDigits($whole).($fraction !== '' ? '.'.$fraction : '').' kg';
    }

    public function businessDate(DateTimeInterface|string $value): string
    {
        return $this->inBusinessTimezone($value)->format('d M Y');
    }

    public function transactionTimestamp(DateTimeInterface|string $value): string
    {
        return $this->inBusinessTimezone($value)->format('d M Y, g:i A');
    }

    private function inBusinessTimezone(DateTimeInterface|string $value): Carbon
    {
        $date = $value instanceof DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value, 'UTC');

        return $date->setTimezone((string) config('app.business_timezone'));
    }

    /** @return array{bool, string, string} */
    private function decimalParts(string|int $value, int $scale): array
    {
        $value = (string) $value;

        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            throw new InvalidArgumentException('A decimal string or integer is required.');
        }

        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        $keptFraction = str_pad(substr($fraction, 0, $scale), $scale, '0');

        if (($fraction[$scale] ?? '0') >= '5') {
            $digits = $this->incrementDigits($whole.$keptFraction);
            $whole = $scale > 0 ? substr($digits, 0, -$scale) : $digits;
            $keptFraction = $scale > 0 ? substr($digits, -$scale) : '';
            $whole = $whole === '' ? '0' : $whole;
        }

        $isZero = trim($whole.$keptFraction, '0') === '';

        return [$negative && ! $isZero, $whole, $keptFraction];
    }

    private function incrementDigits(string $digits): string
    {
        $carry = 1;

        for ($index = strlen($digits) - 1; $index >= 0 && $carry; $index--) {
            $next = ((int) $digits[$index]) + $carry;
            $digits[$index] = (string) ($next % 10);
            $carry = intdiv($next, 10);
        }

        return $carry ? '1'.$digits : $digits;
    }

    private function groupDigits(string $whole): string
    {
        return preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole) ?? $whole;
    }
}
