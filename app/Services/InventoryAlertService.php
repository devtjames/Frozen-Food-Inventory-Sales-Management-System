<?php

namespace App\Services;

use App\Enums\AdminNotificationType;
use App\Enums\StockAlertState;
use App\Models\Product;
use App\Models\StockBatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class InventoryAlertService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly AdminNotificationService $notifications,
    ) {}

    public function sync(Product|int $product): StockAlertState
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('Stock alert transitions must be synchronized inside a database transaction.');
        }

        $productId = $product instanceof Product ? (int) $product->getKey() : $product;
        $lockedProduct = Product::query()->lockForUpdate()->findOrFail($productId);
        $available = $this->availableQuantity($lockedProduct);
        $newState = $this->stateFor($lockedProduct, $available);
        $oldState = $lockedProduct->stock_alert_state;

        if ($oldState === $newState) {
            return $newState;
        }

        $lockedProduct->update(['stock_alert_state' => $newState]);

        if ($newState === StockAlertState::LowStock) {
            $this->notifications->send(
                AdminNotificationType::LowStock,
                "Low stock: {$lockedProduct->name}",
                $this->quantity($available)." available; the alert threshold is {$this->quantity($lockedProduct->low_stock_threshold)}.",
                route('admin.products.show', $lockedProduct),
                $lockedProduct->id,
            );
        }

        if ($newState === StockAlertState::OutOfStock) {
            $this->notifications->send(
                AdminNotificationType::OutOfStock,
                "Out of stock: {$lockedProduct->name}",
                'No sellable stock remains. Further sales are blocked until stock is replenished.',
                route('admin.products.show', $lockedProduct),
                $lockedProduct->id,
            );
        }

        return $newState;
    }

    /** @return Collection<int, Product> */
    public function warnings(int $limit = 8): Collection
    {
        return Product::query()
            ->availableForSale()
            ->with('category:id,name')
            ->withSum('stockBatches as available_quantity_sum', 'remaining_quantity')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product): Product {
                $available = $this->math->normalize((string) ($product->available_quantity_sum ?? 0), 3);
                $product->setAttribute('available_quantity', $available);
                $product->setAttribute('computed_stock_alert_state', $this->stateFor($product, $available));

                return $product;
            })
            ->filter(fn (Product $product): bool => $product->computed_stock_alert_state !== StockAlertState::Normal)
            ->sortBy(fn (Product $product): string => ($product->computed_stock_alert_state === StockAlertState::OutOfStock ? '0-' : '1-').$product->name)
            ->take($limit)
            ->values();
    }

    public function stateFor(Product $product, string $available): StockAlertState
    {
        if ($this->math->compare($available, '0.000', 3) <= 0) {
            return StockAlertState::OutOfStock;
        }

        if ($this->math->compare($available, $product->low_stock_threshold, 3) <= 0) {
            return StockAlertState::LowStock;
        }

        return StockAlertState::Normal;
    }

    private function availableQuantity(Product $product): string
    {
        return StockBatch::query()
            ->where('product_id', $product->id)
            ->where('remaining_quantity', '>', 0)
            ->pluck('remaining_quantity')
            ->reduce(
                fn (string $total, mixed $quantity): string => $this->math->add($total, (string) $quantity, 3),
                '0.000',
            );
    }

    private function quantity(string $quantity): string
    {
        return rtrim(rtrim($this->math->normalize($quantity, 3), '0'), '.').' kg';
    }
}
