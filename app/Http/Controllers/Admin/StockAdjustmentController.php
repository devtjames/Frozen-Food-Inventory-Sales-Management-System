<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockAdjustmentType;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListStockAdjustmentsRequest;
use App\Http\Requests\Admin\StoreStockAdjustmentRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\BusinessFormatter;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function index(ListStockAdjustmentsRequest $request, BusinessFormatter $formatter): View
    {
        $filters = $request->validated();
        $adjustments = StockAdjustment::query()
            ->with(['product.category', 'recorder'])
            ->when($filters['product'] ?? null, fn (Builder $query, int|string $productId) => $query->where('product_id', $productId))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->latest('adjusted_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.inventory.adjustments.index', [
            'adjustments' => $adjustments,
            'products' => Product::query()->orderBy('name')->pluck('name', 'id'),
            'types' => collect(StockAdjustmentType::cases())->mapWithKeys(fn (StockAdjustmentType $type) => [$type->value => $type->label()]),
            'filters' => $filters,
            'formatter' => $formatter,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', StockAdjustment::class);

        return view('admin.inventory.adjustments.create', [
            'products' => Product::query()->orderBy('name')->pluck('name', 'id'),
            'selectedProduct' => request()->integer('product') ?: null,
            'idempotencyKey' => (string) Str::uuid(),
            'defaultAdjustedAt' => Carbon::now(config('app.business_timezone'))->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request, InventoryService $inventory): RedirectResponse
    {
        try {
            $adjustment = $inventory->adjustStock($request->validated(), $request->user());
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()->route('admin.inventory.adjustments.show', $adjustment)
            ->with('success', 'Stock adjustment recorded successfully.');
    }

    public function show(StockAdjustment $adjustment, InventoryService $inventory, BusinessFormatter $formatter): View
    {
        $this->authorize('view', $adjustment);
        $adjustment->load(['product.category', 'batch', 'allocations.batch', 'movement', 'recorder']);

        return view('admin.inventory.adjustments.show', [
            'adjustment' => $adjustment,
            'availableQuantity' => $inventory->availableQuantity($adjustment->product),
            'formatter' => $formatter,
        ]);
    }
}
