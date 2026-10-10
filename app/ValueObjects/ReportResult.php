<?php

namespace App\ValueObjects;

use App\Enums\ReportType;
use Illuminate\Support\Collection;

readonly class ReportResult
{
    /** @param array<string, array{label: string, type: string}> $columns
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<int, array{label: string, value: string|int, type: string}>  $totals
     */
    public function __construct(
        public ReportType $type,
        public array $columns,
        public Collection $rows,
        public array $totals = [],
    ) {}
}
