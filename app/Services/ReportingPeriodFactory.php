<?php

namespace App\Services;

use App\ValueObjects\ReportingPeriod;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class ReportingPeriodFactory
{
    /** @param array<string, mixed> $filters */
    public function make(array $filters, string $default = 'this_month'): ReportingPeriod
    {
        $preset = (string) ($filters['period'] ?? $default);
        $timezone = (string) config('app.business_timezone');
        $now = Carbon::now($timezone);

        [$from, $to, $label] = match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'Today'],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), 'Yesterday'],
            'this_week' => [$now->copy()->startOfWeek()->startOfDay(), $now->copy()->endOfWeek()->endOfDay(), 'This week'],
            'this_month' => [$now->copy()->startOfMonth()->startOfDay(), $now->copy()->endOfMonth()->endOfDay(), 'This month'],
            'custom' => $this->custom($filters, $timezone),
            default => throw new InvalidArgumentException('Unknown reporting period.'),
        };

        return new ReportingPeriod($preset, $from, $to, $label);
    }

    /** @param array<string, mixed> $filters
     * @return array{Carbon, Carbon, string}
     */
    private function custom(array $filters, string $timezone): array
    {
        $from = Carbon::createFromFormat('Y-m-d', (string) $filters['date_from'], $timezone)->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', (string) $filters['date_to'], $timezone)->endOfDay();

        return [$from, $to, $from->format('d M Y').' – '.$to->format('d M Y')];
    }
}
