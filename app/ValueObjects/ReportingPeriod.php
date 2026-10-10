<?php

namespace App\ValueObjects;

use Illuminate\Support\Carbon;

readonly class ReportingPeriod
{
    public function __construct(
        public string $preset,
        public Carbon $fromLocal,
        public Carbon $toLocal,
        public string $label,
    ) {}

    public function fromUtc(): Carbon
    {
        return $this->fromLocal->copy()->utc();
    }

    public function toUtc(): Carbon
    {
        return $this->toLocal->copy()->utc();
    }

    public function fromDate(): string
    {
        return $this->fromLocal->format('Y-m-d');
    }

    public function toDate(): string
    {
        return $this->toLocal->format('Y-m-d');
    }
}
