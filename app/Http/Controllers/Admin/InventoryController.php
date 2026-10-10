<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListInventoryRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\BusinessFormatter;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __invoke(ListInventoryRequest $request, InventoryService $inventory, BusinessFormatter $formatter): View
    {
        $filters = $request->validated();
        $search = $filters['search'] ?? null;
        $categoryId = $filters['category'] ?? null;
        $stockStatus = $filters['stock_status'] ?? null;
        $normalizedSearch = $search ? Str::lower($search) : null;
        $availableStockSql = '(select coalesce(sum(stock_batches.remaining_quantity), 0) from stock_batches where stock_batches.product_id = products.id)';

        $products = Product::query()
            ->with('category')
            ->when($search, fn (Builder $query) => $query->where('normalized_name', 'like', "%{$normalizedSearch}%"))
            ->when($categoryId, fn (Builder $query, int|string $categoryId) => $query->where('category_id', $categoryId))
            ->when($stockStatus === 'low', fn (Builder $query) => $query->whereRaw("{$availableStockSql} <= products.low_stock_threshold"))
            ->when($stockStatus === 'in_stock', fn (Builder $query) => $query->whereRaw("{$availableStockSql} > products.low_stock_threshold"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        $inventory->attachBalances($products->getCollection());

        return view('admin.inventory.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id'),
            'filters' => $filters,
            'formatter' => $formatter,
        ]);
    }
}
