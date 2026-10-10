<?php

namespace App\Services;

use App\Enums\StockLossCategory;
use App\Models\Product;
use App\Models\StockBatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InventoryReportService
{
    public function __construct(private readonly DecimalMath $math) {}

    /** @return array{available_quantity: string, stock_value: string, low_stock_products: int, out_of_stock_products: int} */
    public function summary(): array
    {
        $products = $this->products();
        $available = '0.000';
        $value = '0.00';
        $low = 0;
        $out = 0;

        foreach ($products as $product) {
            $quantity = $this->available($product);
            $available = $this->math->add($available, $quantity, 3);
            $value = $this->math->add($value, $this->value($product), 2);

            if ($product->isAvailableForNewSales() && $this->math->compare($quantity, '0.000', 3) <= 0) {
                $out++;
            }

            if ($product->isAvailableForNewSales() && $this->math->compare($quantity, $product->low_stock_threshold, 3) <= 0) {
                $low++;
            }
        }

        return ['available_quantity' => $available, 'stock_value' => $value, 'low_stock_products' => $low, 'out_of_stock_products' => $out];
    }

    /** @return Collection<int, Product> */
    public function products(?int $productId = null, ?int $categoryId = null): Collection
    {
        return Product::query()->with([
            'category:id,name',
            'stockBatches:id,product_id,remaining_quantity,purchase_cost_per_kg',
        ])->withSum('stockReceipts as received_quantity_total', 'quantity_received')
            ->withSum([
                'saleItems as sold_quantity_total' => fn (Builder $saleItems): Builder => $saleItems
                    ->whereHas('sale', fn (Builder $sales): Builder => $sales->whereDoesntHave('reversal')),
            ], 'quantity')
            ->withSum([
                'stockLosses as damaged_spoiled_quantity_total' => fn (Builder $losses): Builder => $losses
                    ->whereIn('category', [
                        StockLossCategory::Spoiled->value,
                        StockLossCategory::Damaged->value,
                    ])
                    ->whereDoesntHave('reversal'),
            ], 'quantity_lost')
            ->when($productId, fn (Builder $query, int $id): Builder => $query->whereKey($id))
            ->when($categoryId, fn (Builder $query, int $id): Builder => $query->where('category_id', $id))
            ->orderBy('name')->get();
    }

    /** @return array<string, mixed> */
    public function row(Product $product): array
    {
        $available = $this->available($product);
        $received = $this->math->normalize((string) ($product->received_quantity_total ?? 0), 3);
        $sold = $this->math->normalize((string) ($product->sold_quantity_total ?? 0), 3);
        $damaged = $this->math->normalize((string) ($product->damaged_spoiled_quantity_total ?? 0), 3);
        $stockStatus = $this->math->compare($available, '0.000', 3) <= 0
            ? 'Out of stock'
            : ($this->math->compare($available, $product->low_stock_threshold, 3) <= 0 ? 'Low stock' : 'In stock');

        return [
            'product' => $product->name,
            'category' => $product->category->name,
            'available_quantity' => $available,
            'received_quantity' => $received,
            'sold_quantity' => $sold,
            'damaged_quantity' => $damaged,
            'inventory_value' => $this->value($product),
            'stock_status' => $stockStatus,
            'product_status' => $product->isAvailableForNewSales() ? 'Active' : 'Inactive',
        ];
    }

    private function available(Product $product): string
    {
        return $product->stockBatches->reduce(fn (string $sum, StockBatch $batch): string => $this->math->add($sum, $batch->remaining_quantity, 3), '0.000');
    }

    private function value(Product $product): string
    {
        return $product->stockBatches->reduce(fn (string $sum, StockBatch $batch): string => $this->math->add(
            $sum,
            $this->math->multiply($batch->remaining_quantity, 3, $batch->purchase_cost_per_kg, 2, 2),
            2,
        ), '0.00');
    }
}
