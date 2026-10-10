<?php

namespace Tests\Unit;

use App\Services\DecimalMath;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DecimalMathTest extends TestCase
{
    public function test_decimal_operations_do_not_use_floating_point_values(): void
    {
        $math = new DecimalMath;

        $this->assertSame('150.125', $math->add('100.000', '50.125', 3));
        $this->assertSame('49.875', $math->subtract('100.000', '50.125', 3));
        $this->assertSame('350000.00', $math->multiply('100.000', 3, '3500.00', 2, 2));
        $this->assertSame('3750.00', $math->multiply('1.125', 3, '3333.33', 2, 2));
        $this->assertSame('-2.500', $math->negate('2.5', 3));
    }

    public function test_excess_precision_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DecimalMath)->normalize('1.0001', 3);
    }

    public function test_division_rounds_half_up_without_binary_floating_point(): void
    {
        $math = new DecimalMath;

        $this->assertSame('1.111', $math->divide('5000.00', 2, '4500.00', 2, 3));
        $this->assertSame('1.250', $math->divide('5000.00', 2, '4000.00', 2, 3));
        $this->assertSame('1.001', $math->divide('100.05', 2, '100.00', 2, 3));
    }
}
