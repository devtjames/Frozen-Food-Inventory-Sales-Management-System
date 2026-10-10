<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportType;
use App\Enums\StockLossCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRequest;
use App\Models\Category;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\User;
use App\Services\BusinessFormatter;
use App\Services\ReportDataService;
use App\Services\ReportingPeriodFactory;
use App\ValueObjects\ReportResult;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function show(ReportRequest $request, string $report, ReportingPeriodFactory $periods, ReportDataService $reports, BusinessFormatter $formatter): View
    {
        $type = $this->type($report);
        $filters = $request->validated();
        $period = $periods->make($filters);
        $result = $reports->generate($type, $period, $filters);

        return view('admin.reports.show', $this->viewData($result, $period, $filters, $formatter) + [
            'rows' => $this->paginate($result->rows),
        ]);
    }

    public function export(ReportRequest $request, string $report, ReportingPeriodFactory $periods, ReportDataService $reports): StreamedResponse
    {
        $type = $this->type($report);
        $filters = $request->validated();
        $period = $periods->make($filters);
        $result = $reports->generate($type, $period, $filters);
        $filename = $type->value.'-'.$period->fromDate().'-to-'.$period->toDate().'.csv';

        return response()->streamDownload(function () use ($result): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, collect($result->columns)->pluck('label')->all());

            foreach ($result->rows as $row) {
                fputcsv($stream, collect($result->columns)->map(fn (array $column, string $key): string => $this->csvValue($row[$key] ?? '', $column['type']))->all());
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function type(string $report): ReportType
    {
        return ReportType::tryFrom($report) ?? abort(404);
    }

    private function paginate(Collection $rows): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator($rows->forPage($page, 15)->values(), $rows->count(), 15, $page, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);

        return $paginator->appends(request()->except('page'));
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function viewData(ReportResult $result, $period, array $filters, BusinessFormatter $formatter): array
    {
        return [
            'result' => $result,
            'period' => $period,
            'filters' => $filters,
            'formatter' => $formatter,
            'reportTypes' => ReportType::cases(),
            'products' => Product::query()->orderBy('name')->pluck('name', 'id')->all(),
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id')->all(),
            'cashiers' => User::query()->orderBy('name')->pluck('name', 'id')->all(),
            'expenseCategories' => ExpenseCategory::query()->orderBy('name')->pluck('name', 'id')->all(),
            'lossCategories' => collect(StockLossCategory::cases())->mapWithKeys(fn (StockLossCategory $category): array => [$category->value => $category->label()])->all(),
        ];
    }

    private function csvValue(mixed $value, string $type): string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $type === 'date'
                ? $value->format('Y-m-d')
                : $value->setTimezone(new \DateTimeZone((string) config('app.business_timezone')))->format('Y-m-d H:i:s');
        }

        $value = (string) $value;

        return in_array(substr($value, 0, 1), ['=', '+', '-', '@'], true) && ! in_array($type, ['money', 'kg', 'integer'], true)
            ? "'".$value
            : $value;
    }
}
