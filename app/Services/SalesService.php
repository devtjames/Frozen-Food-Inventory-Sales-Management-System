<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\CheckoutValidationException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\ProductPriceChangedException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SalesService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SaleCalculator $calculator,
        private readonly FifoStockAllocator $fifoAllocator,
        private readonly InventoryAlertService $alerts,
    ) {}

    /** @param array<int, array<string, mixed>> $cart */
    public function checkout(array $cart, string $paymentMethod, ?string $amountTendered, string $checkoutKey, User $cashier): Sale
    {
        if (! $cashier->canProcessSales()) {
            throw new CheckoutValidationException('Only an active cashier or administrator can complete a sale.');
        }

        if ($cart === [] || count($cart) > 50) {
            throw new CheckoutValidationException('Add between 1 and 50 items before checkout.');
        }

        if ($existing = Sale::query()->where('checkout_key', $checkoutKey)->first()) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($cart, $paymentMethod, $amountTendered, $checkoutKey, $cashier): Sale {
                if ($existing = Sale::query()->where('checkout_key', $checkoutKey)->lockForUpdate()->first()) {
                    return $existing;
                }

                $method = PaymentMethod::from($paymentMethod);
                $productIds = collect($cart)->pluck('product_id')->map(fn (mixed $id): int => (int) $id)->unique()->sort()->values();
                $products = Product::query()->with('category')->whereKey($productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                if ($products->count() !== $productIds->count()) {
                    throw new CheckoutValidationException('One or more selected products no longer exist.');
                }

                $prepared = $this->prepareLines($cart, $products);
                $this->assertCombinedAvailability($prepared);
                $total = $this->sum($prepared, 'item_amount');
                $roundingTotal = $this->sum($prepared, 'rounding_adjustment');
                [$tendered, $change] = $this->cashDetails($method, $amountTendered, $total);
                $soldAt = now()->utc();

                $sale = Sale::query()->create([
                    'checkout_key' => $checkoutKey,
                    'transaction_number' => 'SALE-'.now(config('app.business_timezone'))->format('Ymd').'-'.Str::upper((string) Str::ulid()),
                    'cashier_id' => $cashier->id,
                    'sold_at' => $soldAt,
                    'total_amount' => $total,
                    'total_rounding_adjustment' => $roundingTotal,
                    'payment_method' => $method,
                    'payment_status' => PaymentStatus::Paid,
                    'transaction_status' => SaleStatus::Completed,
                    'amount_tendered' => $tendered,
                    'change_due' => $change,
                ]);

                foreach ($prepared as $line) {
                    $item = $sale->items()->create([
                        'product_id' => $line['product']->id,
                        'category_id' => $line['product']->category_id,
                        'product_name' => $line['product']->name,
                        'category_name' => $line['product']->category->name,
                        'selling_price_per_kg' => $line['product']->selling_price,
                        'selling_method' => $line['selling_method'],
                        'requested_amount' => $line['requested_amount'],
                        'requested_quantity' => $line['requested_quantity'],
                        'quantity' => $line['quantity'],
                        'item_amount' => $line['item_amount'],
                        'calculated_value' => $line['calculated_value'],
                        'rounding_adjustment' => $line['rounding_adjustment'],
                        'purchase_cost_total' => '0.00',
                    ]);

                    $allocation = $this->fifoAllocator->allocate($line['product'], $line['quantity'], $item, $soldAt);
                    $item->update(['purchase_cost_total' => $allocation->totalCost]);
                    $item->movement()->create([
                        'product_id' => $line['product']->id,
                        'stock_batch_id' => $allocation->allocations->count() === 1 ? $allocation->allocations->first()->stock_batch_id : null,
                        'movement_type' => StockMovementType::Sale,
                        'quantity_delta' => $this->math->negate($line['quantity'], 3),
                        'occurred_at' => $soldAt,
                        'recorded_by' => $cashier->id,
                    ]);
                }

                $sale->payment()->create([
                    'method' => $method,
                    'amount' => $total,
                    'amount_tendered' => $tendered,
                    'change_due' => $change,
                    'paid_at' => $soldAt,
                    'recorded_by' => $cashier->id,
                ]);

                foreach ($products as $product) {
                    $this->alerts->sync($product);
                }

                return $sale->load(['items.allocations.batch', 'items.movement', 'payment', 'cashier']);
            });
        } catch (QueryException $exception) {
            if ($existing = Sale::query()->where('checkout_key', $checkoutKey)->first()) {
                return $existing;
            }

            throw $exception;
        }
    }

    /** @param array<int, array<string, mixed>> $cart
     * @param  Collection<int, Product>  $products
     * @return array<int, array<string, mixed>>
     */
    private function prepareLines(array $cart, Collection $products): array
    {
        return collect($cart)->map(function (array $line) use ($products): array {
            $product = $products->get((int) ($line['product_id'] ?? 0));

            if (! $product || ! $product->isAvailableForNewSales()) {
                throw new CheckoutValidationException('A product in the cart is inactive or unavailable for sale.');
            }

            try {
                if ($this->math->compare((string) ($line['selling_price_per_kg'] ?? ''), $product->selling_price, 2) !== 0) {
                    throw new ProductPriceChangedException($product->name);
                }
            } catch (InvalidArgumentException $exception) {
                throw new CheckoutValidationException('A cart price snapshot is invalid.', previous: $exception);
            }

            return ['product' => $product] + $this->calculator->line(
                $product->selling_price,
                (string) ($line['selling_method'] ?? ''),
                (string) ($line['input_value'] ?? ''),
            );
        })->all();
    }

    /** @param array<int, array<string, mixed>> $prepared */
    private function assertCombinedAvailability(array $prepared): void
    {
        $requiredByProduct = [];

        foreach ($prepared as $line) {
            $productId = $line['product']->id;
            $requiredByProduct[$productId] = $this->math->add($requiredByProduct[$productId] ?? '0.000', $line['quantity'], 3);
        }

        foreach ($requiredByProduct as $productId => $required) {
            $available = StockBatch::query()->where('product_id', $productId)->where('remaining_quantity', '>', 0)->lockForUpdate()->pluck('remaining_quantity')
                ->reduce(fn (string $sum, mixed $quantity): string => $this->math->add($sum, (string) $quantity, 3), '0.000');

            if ($this->math->compare($available, $required, 3) < 0) {
                throw new InsufficientStockException($required, $available);
            }
        }
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function sum(array $lines, string $field): string
    {
        return collect($lines)->reduce(fn (string $sum, array $line): string => $this->math->add($sum, $line[$field], 2), '0.00');
    }

    /** @return array{?string, ?string} */
    private function cashDetails(PaymentMethod $method, ?string $amountTendered, string $total): array
    {
        if ($method !== PaymentMethod::Cash || $amountTendered === null || trim($amountTendered) === '') {
            return [null, null];
        }

        try {
            $tendered = $this->math->normalize($amountTendered, 2);
        } catch (InvalidArgumentException $exception) {
            throw new CheckoutValidationException($exception->getMessage(), previous: $exception);
        }

        if ($this->math->compare($tendered, $total, 2) < 0) {
            throw new CheckoutValidationException('The cash tendered cannot be less than the sale total.');
        }

        return [$tendered, $this->math->subtract($tendered, $total, 2)];
    }
}
