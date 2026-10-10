<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockLossCategory;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockBatchException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListStockLossesRequest;
use App\Http\Requests\Admin\StoreStockLossRequest;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockLoss;
use App\Services\BusinessFormatter;
use App\Services\InventoryService;
use App\Services\StockLossService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StockLossController extends Controller
{
    public function index(ListStockLossesRequest $request, BusinessFormatter $formatter): View
    {
        $filters = $request->validated();
        $search = $filters['search'] ?? null;
        $timezone = (string) config('app.business_timezone');

        $losses = StockLoss::query()
            ->with(['product.category', 'recorder', 'reversal'])
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('reason', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('product', fn (Builder $query) => $query->where('normalized_name', 'like', '%'.Str::lower($search).'%'));
                });
            })
            ->when($filters['product'] ?? null, fn (Builder $query, int|string $productId) => $query->where('product_id', $productId))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->active())
            ->when(($filters['status'] ?? null) === 'reversed', fn (Builder $query) => $query->reversed())
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->where('lost_at', '>=', Carbon::parse($date, $timezone)->startOfDay()->utc()))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->where('lost_at', '<=', Carbon::parse($date, $timezone)->endOfDay()->utc()))
            ->latest('lost_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.inventory.losses.index', [
            'losses' => $losses,
            'products' => Product::query()->orderBy('name')->pluck('name', 'id'),
            'categories' => $this->categoryOptions(),
            'filters' => $filters,
            'formatter' => $formatter,
        ]);
    }

    public function create(InventoryService $inventory, BusinessFormatter $formatter): View
    {
        $this->authorize('create', StockLoss::class);
        $products = Product::query()->orderBy('name')->get();
        $inventory->attachBalances($products);
        $batches = StockBatch::query()
            ->where('remaining_quantity', '>', 0)
            ->orderBy('product_id')
            ->orderBy('received_at')
            ->orderBy('id')
            ->get();

        return view('admin.inventory.losses.create', [
            'productOptions' => $products->pluck('name', 'id'),
            'productBalances' => $products->mapWithKeys(fn (Product $product): array => [(string) $product->id => $product->available_quantity]),
            'batchOptions' => $batches->map(fn (StockBatch $batch): array => [
                'id' => (string) $batch->id,
                'product_id' => (string) $batch->product_id,
                'remaining' => $batch->remaining_quantity,
                'label' => "Batch #{$batch->id} — {$formatter->kilograms($batch->remaining_quantity)} available at {$formatter->money($batch->purchase_cost_per_kg)}/kg",
            ])->values(),
            'categories' => $this->categoryOptions(),
            'selectedProduct' => request()->integer('product') ?: null,
            'idempotencyKey' => (string) Str::uuid(),
            'defaultLostAt' => Carbon::now(config('app.business_timezone'))->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(StoreStockLossRequest $request, StockLossService $service): RedirectResponse
    {
        try {
            $loss = $service->record($request->validated(), $request->user());
        } catch (InsufficientStockException|InvalidStockBatchException $exception) {
            return back()->withInput()->withErrors(['quantity_lost' => $exception->getMessage()]);
        }

        return redirect()->route('admin.inventory.losses.show', $loss)
            ->with('success', 'Stock loss recorded and inventory deducted successfully.');
    }

    public function show(StockLoss $loss, InventoryService $inventory, BusinessFormatter $formatter): View
    {
        $this->authorize('view', $loss);
        $loss->load(['product.category', 'selectedBatch', 'allocations.batch', 'movement', 'recorder', 'reversal.recorder', 'reversal.movement']);

        return view('admin.inventory.losses.show', [
            'loss' => $loss,
            'availableQuantity' => $inventory->availableQuantity($loss->product),
            'formatter' => $formatter,
        ]);
    }

    /** @return array<string, string> */
    private function categoryOptions(): array
    {
        return collect(StockLossCategory::cases())
            ->mapWithKeys(fn (StockLossCategory $category): array => [$category->value => $category->label()])
            ->all();
    }
}
