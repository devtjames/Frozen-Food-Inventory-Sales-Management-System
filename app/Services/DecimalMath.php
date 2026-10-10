<?php

namespace App\Services;

use DivisionByZeroError;
use InvalidArgumentException;
use LogicException;

class DecimalMath
{
    public function normalize(string|int $value, int $scale): string
    {
        [$negative, $digits] = $this->scaledDigits($value, $scale);

        return $this->fromScaledDigits($digits, $scale, $negative);
    }

    public function compare(string|int $left, string|int $right, int $scale): int
    {
        [$leftNegative, $leftDigits] = $this->scaledDigits($left, $scale);
        [$rightNegative, $rightDigits] = $this->scaledDigits($right, $scale);

        if ($leftNegative !== $rightNegative) {
            return $leftNegative ? -1 : 1;
        }

        $comparison = $this->compareDigits($leftDigits, $rightDigits);

        return $leftNegative ? -$comparison : $comparison;
    }

    public function add(string|int $left, string|int $right, int $scale): string
    {
        [$leftNegative, $leftDigits] = $this->scaledDigits($left, $scale);
        [$rightNegative, $rightDigits] = $this->scaledDigits($right, $scale);

        if ($leftNegative === $rightNegative) {
            return $this->fromScaledDigits($this->addDigits($leftDigits, $rightDigits), $scale, $leftNegative);
        }

        $comparison = $this->compareDigits($leftDigits, $rightDigits);

        if ($comparison === 0) {
            return $this->fromScaledDigits('0', $scale, false);
        }

        $leftIsLarger = $comparison > 0;
        $digits = $leftIsLarger
            ? $this->subtractDigits($leftDigits, $rightDigits)
            : $this->subtractDigits($rightDigits, $leftDigits);
        $negative = $leftIsLarger ? $leftNegative : $rightNegative;

        return $this->fromScaledDigits($digits, $scale, $negative);
    }

    public function subtract(string|int $left, string|int $right, int $scale): string
    {
        return $this->add($left, $this->negate($right, $scale), $scale);
    }

    public function multiply(
        string|int $left,
        int $leftScale,
        string|int $right,
        int $rightScale,
        int $resultScale,
    ): string {
        [$leftNegative, $leftDigits] = $this->scaledDigits($left, $leftScale);
        [$rightNegative, $rightDigits] = $this->scaledDigits($right, $rightScale);
        $digits = $this->multiplyDigits($leftDigits, $rightDigits);
        $sourceScale = $leftScale + $rightScale;

        if ($sourceScale > $resultScale) {
            $discardCount = $sourceScale - $resultScale;
            $digits = str_pad($digits, $discardCount + 1, '0', STR_PAD_LEFT);
            $roundingDigit = $digits[strlen($digits) - $discardCount];
            $digits = substr($digits, 0, -$discardCount) ?: '0';

            if ($roundingDigit >= '5') {
                $digits = $this->addDigits($digits, '1');
            }
        } elseif ($sourceScale < $resultScale) {
            $digits .= str_repeat('0', $resultScale - $sourceScale);
        }

        return $this->fromScaledDigits($digits, $resultScale, $leftNegative !== $rightNegative);
    }

    public function divide(
        string|int $left,
        int $leftScale,
        string|int $right,
        int $rightScale,
        int $resultScale,
    ): string {
        [$leftNegative, $leftDigits] = $this->scaledDigits($left, $leftScale);
        [$rightNegative, $rightDigits] = $this->scaledDigits($right, $rightScale);

        if ($rightDigits === '0') {
            throw new DivisionByZeroError('Cannot divide by zero.');
        }

        $numerator = $leftDigits.str_repeat('0', $rightScale + $resultScale);
        $denominator = $rightDigits.str_repeat('0', $leftScale);
        [$quotient, $remainder] = $this->divideDigits($numerator, $denominator);

        if ($this->compareDigits($this->addDigits($remainder, $remainder), $denominator) >= 0) {
            $quotient = $this->addDigits($quotient, '1');
        }

        return $this->fromScaledDigits($quotient, $resultScale, $leftNegative !== $rightNegative);
    }

    public function negate(string|int $value, int $scale): string
    {
        [$negative, $digits] = $this->scaledDigits($value, $scale);

        return $this->fromScaledDigits($digits, $scale, ! $negative);
    }

    public function min(string|int $left, string|int $right, int $scale): string
    {
        return $this->compare($left, $right, $scale) <= 0
            ? $this->normalize($left, $scale)
            : $this->normalize($right, $scale);
    }

    /** @return array{bool, string} */
    private function scaledDigits(string|int $value, int $scale): array
    {
        $value = trim((string) $value);

        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            throw new InvalidArgumentException('A decimal string or integer is required.');
        }

        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');

        if (strlen($fraction) > $scale) {
            throw new InvalidArgumentException("The value may contain at most {$scale} decimal places.");
        }

        $digits = ltrim($whole.str_pad($fraction, $scale, '0'), '0') ?: '0';

        return [$negative && $digits !== '0', $digits];
    }

    private function fromScaledDigits(string $digits, int $scale, bool $negative): string
    {
        $digits = ltrim($digits, '0') ?: '0';
        $digits = str_pad($digits, $scale + 1, '0', STR_PAD_LEFT);
        $whole = $scale > 0 ? substr($digits, 0, -$scale) : $digits;
        $fraction = $scale > 0 ? '.'.substr($digits, -$scale) : '';

        return ($negative && $digits !== str_repeat('0', strlen($digits)) ? '-' : '').$whole.$fraction;
    }

    private function compareDigits(string $left, string $right): int
    {
        $left = ltrim($left, '0') ?: '0';
        $right = ltrim($right, '0') ?: '0';

        return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
    }

    private function addDigits(string $left, string $right): string
    {
        $length = max(strlen($left), strlen($right));
        $left = str_pad($left, $length, '0', STR_PAD_LEFT);
        $right = str_pad($right, $length, '0', STR_PAD_LEFT);
        $carry = 0;
        $result = '';

        for ($index = $length - 1; $index >= 0; $index--) {
            $sum = (int) $left[$index] + (int) $right[$index] + $carry;
            $result = ($sum % 10).$result;
            $carry = intdiv($sum, 10);
        }

        return ($carry ? $carry : '').$result;
    }

    private function subtractDigits(string $left, string $right): string
    {
        if ($this->compareDigits($left, $right) < 0) {
            throw new LogicException('Unsigned subtraction would produce a negative result.');
        }

        $length = max(strlen($left), strlen($right));
        $left = str_pad($left, $length, '0', STR_PAD_LEFT);
        $right = str_pad($right, $length, '0', STR_PAD_LEFT);
        $borrow = 0;
        $result = '';

        for ($index = $length - 1; $index >= 0; $index--) {
            $difference = (int) $left[$index] - (int) $right[$index] - $borrow;

            if ($difference < 0) {
                $difference += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }

            $result = $difference.$result;
        }

        return ltrim($result, '0') ?: '0';
    }

    private function multiplyDigits(string $left, string $right): string
    {
        if ($left === '0' || $right === '0') {
            return '0';
        }

        $result = array_fill(0, strlen($left) + strlen($right), 0);

        for ($leftIndex = strlen($left) - 1; $leftIndex >= 0; $leftIndex--) {
            for ($rightIndex = strlen($right) - 1; $rightIndex >= 0; $rightIndex--) {
                $position = $leftIndex + $rightIndex + 1;
                $result[$position] += (int) $left[$leftIndex] * (int) $right[$rightIndex];
                $result[$position - 1] += intdiv($result[$position], 10);
                $result[$position] %= 10;
            }
        }

        return ltrim(implode('', $result), '0') ?: '0';
    }

    /** @return array{string, string} */
    private function divideDigits(string $numerator, string $denominator): array
    {
        $quotient = '';
        $remainder = '0';

        foreach (str_split(ltrim($numerator, '0') ?: '0') as $digit) {
            $remainder = ltrim(($remainder === '0' ? '' : $remainder).$digit, '0') ?: '0';
            $quotientDigit = 0;

            while ($this->compareDigits($remainder, $denominator) >= 0) {
                $remainder = $this->subtractDigits($remainder, $denominator);
                $quotientDigit++;
            }

            $quotient .= (string) $quotientDigit;
        }

        return [ltrim($quotient, '0') ?: '0', $remainder];
    }
}
