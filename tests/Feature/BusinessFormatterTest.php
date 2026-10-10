<?php

namespace Tests\Feature;

use App\Services\BusinessFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class BusinessFormatterTest extends TestCase
{
    use RefreshDatabase;

    public function test_money_is_formatted_without_using_floating_point_calculation(): void
    {
        $formatter = app(BusinessFormatter::class);

        $this->assertSame('₦1,234,567.50', $formatter->money('1234567.5'));
        $this->assertSame('-₦5.13', $formatter->money('-5.125'));
        $this->assertSame('₦1,000.00', $formatter->money('999.999'));
    }

    public function test_kilogram_quantities_are_formatted_consistently(): void
    {
        $formatter = app(BusinessFormatter::class);

        $this->assertSame('2.5 kg', $formatter->kilograms('2.500'));
        $this->assertSame('0.126 kg', $formatter->kilograms('0.1255'));
        $this->assertSame('1,250 kg', $formatter->kilograms(1250));
    }

    public function test_dates_and_timestamps_are_converted_from_utc_to_lagos_time(): void
    {
        $formatter = app(BusinessFormatter::class);
        $utc = Carbon::create(2026, 10, 9, 23, 30, 0, 'UTC');

        $this->assertSame('10 Oct 2026', $formatter->businessDate($utc));
        $this->assertSame('10 Oct 2026, 12:30 AM', $formatter->transactionTimestamp($utc));
    }

    public function test_invalid_decimal_input_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(BusinessFormatter::class)->money('1,200.00');
    }
}
