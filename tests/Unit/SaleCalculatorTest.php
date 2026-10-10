<?php

namespace Tests\Unit;

use App\Exceptions\CheckoutValidationException;
use App\Services\DecimalMath;
use App\Services\SaleCalculator;
use PHPUnit\Framework\TestCase;

class SaleCalculatorTest extends TestCase
{
    public function test_amount_sale_preserves_payable_amount_and_exposes_rounding_difference(): void
    {
        $line = (new SaleCalculator(new DecimalMath))->line('4500.00', 'amount', '5000.00');

        $this->assertSame('1.111', $line['quantity']);
        $this->assertSame('5000.00', $line['item_amount']);
        $this->assertSame('4999.50', $line['calculated_value']);
        $this->assertSame('0.50', $line['rounding_adjustment']);
    }

    public function test_kilogram_sale_calculates_money_exactly(): void
    {
        $line = (new SaleCalculator(new DecimalMath))->line('4500.00', 'kilograms', '2.500');

        $this->assertSame('2.500', $line['quantity']);
        $this->assertSame('11250.00', $line['item_amount']);
        $this->assertSame('0.00', $line['rounding_adjustment']);
    }

    public function test_amount_sale_at_four_thousand_naira_per_kilogram_is_exact(): void
    {
        $line = (new SaleCalculator(new DecimalMath))->line('4000.00', 'amount', '5000.00');

        $this->assertSame('1.250', $line['quantity']);
        $this->assertSame('5000.00', $line['item_amount']);
        $this->assertSame('5000.00', $line['calculated_value']);
        $this->assertSame('0.00', $line['rounding_adjustment']);
    }

    public function test_repeating_decimal_weight_is_rounded_to_three_places_with_visible_adjustment(): void
    {
        $line = (new SaleCalculator(new DecimalMath))->line('3000.00', 'amount', '5000.00');

        $this->assertSame('1.667', $line['quantity']);
        $this->assertSame('5000.00', $line['item_amount']);
        $this->assertSame('5001.00', $line['calculated_value']);
        $this->assertSame('-1.00', $line['rounding_adjustment']);
    }

    public function test_amount_that_cannot_produce_minimum_weight_is_rejected(): void
    {
        $this->expectException(CheckoutValidationException::class);

        (new SaleCalculator(new DecimalMath))->line('15000.00', 'amount', '0.01');
    }
}
