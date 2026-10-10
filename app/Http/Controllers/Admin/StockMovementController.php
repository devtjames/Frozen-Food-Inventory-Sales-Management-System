<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListStockMovementsRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\BusinessFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function __invoke(ListStockMovementsRequest $request, BusinessFormatter $formatter): View
    {
        $filters = $request->validated();
        $movements = StockMovement::query()
            ->with(['product.category', 'batch', 'recorder', 'source'])
            ->when($filters['product'] ?? null, fn (Builder $query, int|string $productId) => $query->where('product_id', $productId))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('movement_type', $type))
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.inventory.movements.index', [
            'movements' => $movements,
            'products' => Product::query()->orderBy('name')->pluck('name', 'id'),
            'movementTypes' => collect(StockMovementType::cases())->mapWithKeys(fn (StockMovementType $type) => [$type->value => $type->label()]),
            'filters' => $filters,
            'formatter' => $formatter,
        ]);
    }
}
