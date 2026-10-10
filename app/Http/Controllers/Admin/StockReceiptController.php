<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListStockReceiptsRequest;
use App\Http\Requests\Admin\StoreStockReceiptRequest;
use App\Models\Product;
use App\Models\StockReceipt;
use App\Services\BusinessFormatter;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StockReceiptController extends Controller
{
    public function index(ListStockReceiptsRequest $request, BusinessFormatter $formatter): View
    {
        $filters = $request->validated();
        $search = $filters['search'] ?? null;
        $productId = $filters['product'] ?? null;
        $receipts = StockReceipt::query()
            ->with(['product.category', 'batch', 'recorder'])
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('supplier_name', 'like', "%{$search}%")
                        ->orWhereHas('product', fn (Builder $query) => $query->where('normalized_name', 'like', '%'.Str::lower($search).'%'));
                });
            })
            ->when($productId, fn (Builder $query, int|string $productId) => $query->where('product_id', $productId))
            ->latest('received_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.inventory.receipts.index', [
            'receipts' => $receipts,
            'products' => $this->productOptions(),
            'filters' => $filters,
            'formatter' => $formatter,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', StockReceipt::class);

        return view('admin.inventory.receipts.create', [
            'products' => $this->productOptions(),
            'selectedProduct' => request()->integer('product') ?: null,
            'idempotencyKey' => (string) Str::uuid(),
            'defaultReceivedAt' => Carbon::now(config('app.business_timezone'))->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(StoreStockReceiptRequest $request, InventoryService $inventory): RedirectResponse
    {
        $receipt = $inventory->receiveStock($request->validated(), $request->user());

        return redirect()->route('admin.inventory.receipts.show', $receipt)
            ->with('success', 'Stock received successfully.');
    }

    public function show(StockReceipt $receipt, InventoryService $inventory, BusinessFormatter $formatter): View
    {
        $this->authorize('view', $receipt);
        $receipt->load(['product.category', 'batch.allocations', 'movement', 'recorder']);

        return view('admin.inventory.receipts.show', [
            'receipt' => $receipt,
            'availableQuantity' => $inventory->availableQuantity($receipt->product),
            'formatter' => $formatter,
        ]);
    }

    /** @return Collection<int|string, string> */
    private function productOptions()
    {
        return Product::query()->orderBy('name')->get()->mapWithKeys(fn (Product $product): array => [
            $product->id => $product->name.($product->is_active ? '' : ' (Inactive)'),
        ]);
    }
}
