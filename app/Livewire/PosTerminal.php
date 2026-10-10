<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Enums\SellingMethod;
use App\Exceptions\CheckoutValidationException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\ProductPriceChangedException;
use App\Models\Category;
use App\Models\Product;
use App\Services\DecimalMath;
use App\Services\InventoryService;
use App\Services\SaleCalculator;
use App\Services\SalesService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PosTerminal extends Component
{
    public string $search = '';

    public string $category = '';

    public string $selectedProductId = '';

    public string $sellingMethod = 'amount';

    public string $inputValue = '';

    /** @var array<int, array<string, mixed>> */
    public array $cart = [];

    public string $paymentMethod = 'cash';

    public string $amountTendered = '';

    #[Locked]
    public string $checkoutKey;

    public function mount(): void
    {
        $this->authorizeSalesOperator();
        $this->checkoutKey = (string) Str::uuid();
    }

    #[Computed]
    public function products()
    {
        $this->authorizeSalesOperator();
        $products = Product::query()
            ->with('category')
            ->availableForSale()
            ->whereHas('stockBatches', fn ($query) => $query->where('remaining_quantity', '>', 0))
            ->when(trim($this->search) !== '', fn ($query) => $query->where('normalized_name', 'like', '%'.Str::lower(trim($this->search)).'%'))
            ->when($this->category !== '', fn ($query) => $query->where('category_id', $this->category))
            ->orderBy('name')
            ->limit(24)
            ->get();
        app(InventoryService::class)->attachBalances($products);

        return $products->filter(fn (Product $product): bool => app(DecimalMath::class)->compare($product->available_quantity, '0.000', 3) > 0);
    }

    #[Computed]
    public function categories()
    {
        $this->authorizeSalesOperator();

        return Category::query()->active()
            ->whereHas('products', fn ($query) => $query->availableForSale()->whereHas('stockBatches', fn ($query) => $query->where('remaining_quantity', '>', 0)))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedProduct(): ?Product
    {
        if ($this->selectedProductId === '') {
            return null;
        }

        $product = Product::query()->with('category')->availableForSale()->find($this->selectedProductId);

        if ($product) {
            app(InventoryService::class)->attachBalances(collect([$product]));
        }

        return $product;
    }

    #[Computed]
    public function preview(): ?array
    {
        if (! $this->selectedProduct || trim($this->inputValue) === '') {
            return null;
        }

        try {
            return app(SaleCalculator::class)->line($this->selectedProduct->selling_price, $this->sellingMethod, $this->inputValue);
        } catch (CheckoutValidationException) {
            return null;
        }
    }

    #[Computed]
    public function cartTotal(): string
    {
        return collect($this->cart)->reduce(fn (string $total, array $item): string => app(DecimalMath::class)->add($total, (string) $item['item_amount'], 2), '0.00');
    }

    #[Computed]
    public function roundingTotal(): string
    {
        return collect($this->cart)->reduce(fn (string $total, array $item): string => app(DecimalMath::class)->add($total, (string) $item['rounding_adjustment'], 2), '0.00');
    }

    #[Computed]
    public function cashChange(): ?string
    {
        if ($this->paymentMethod !== PaymentMethod::Cash->value || trim($this->amountTendered) === '') {
            return null;
        }

        try {
            $tendered = app(DecimalMath::class)->normalize($this->amountTendered, 2);

            return app(DecimalMath::class)->compare($tendered, $this->cartTotal, 2) >= 0
                ? app(DecimalMath::class)->subtract($tendered, $this->cartTotal, 2)
                : null;
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    public function selectProduct(int $productId): void
    {
        $this->authorizeSalesOperator();
        $this->selectedProductId = (string) $productId;
        $this->inputValue = '';
        $this->resetErrorBag('inputValue');
        unset($this->selectedProduct, $this->preview);
    }

    public function addItem(SaleCalculator $calculator, InventoryService $inventory, DecimalMath $math): void
    {
        $this->authorizeSalesOperator();
        $this->validate([
            'selectedProductId' => ['required', 'integer', 'exists:products,id'],
            'sellingMethod' => ['required', Rule::enum(SellingMethod::class)],
            'inputValue' => ['required', 'string', 'max:30'],
        ]);
        $product = Product::query()->with('category')->availableForSale()->find($this->selectedProductId);

        if (! $product) {
            $this->addError('selectedProductId', 'This product is not available for sale.');

            return;
        }

        try {
            $calculation = $calculator->line($product->selling_price, $this->sellingMethod, $this->inputValue);
            $combined = collect($this->cart)->where('product_id', $product->id)
                ->reduce(fn (string $sum, array $item): string => $math->add($sum, (string) $item['quantity'], 3), $calculation['quantity']);
            $available = $inventory->availableQuantity($product);

            if ($math->compare($combined, $available, 3) > 0) {
                throw new InsufficientStockException($combined, $available);
            }

            $this->cart[] = [
                'line_id' => (string) Str::uuid(),
                'product_id' => $product->id,
                'product_name' => $product->name,
                'selling_price_per_kg' => $product->selling_price,
                'input_value' => $this->inputValue,
            ] + $calculation;
            $this->inputValue = '';
            $this->resetErrorBag();
            unset($this->cartTotal, $this->roundingTotal, $this->preview);
        } catch (CheckoutValidationException|InsufficientStockException $exception) {
            $this->addError('inputValue', $exception->getMessage());
        }
    }

    public function updateItem(int $index, SaleCalculator $calculator, InventoryService $inventory, DecimalMath $math): void
    {
        $this->authorizeSalesOperator();

        if (! isset($this->cart[$index])) {
            return;
        }

        $item = $this->cart[$index];
        $product = Product::query()->with('category')->availableForSale()->find($item['product_id']);

        if (! $product) {
            $this->addError("cart.{$index}.input_value", 'This product is no longer available for sale.');

            return;
        }

        try {
            $calculation = $calculator->line($product->selling_price, $item['selling_method'], (string) $item['input_value']);
            $combined = collect($this->cart)->except($index)->where('product_id', $product->id)
                ->reduce(fn (string $sum, array $other): string => $math->add($sum, (string) $other['quantity'], 3), $calculation['quantity']);
            $available = $inventory->availableQuantity($product);

            if ($math->compare($combined, $available, 3) > 0) {
                throw new InsufficientStockException($combined, $available);
            }

            $this->cart[$index] = array_merge($item, $calculation);
            $this->cart[$index]['selling_price_per_kg'] = $product->selling_price;
            $this->resetErrorBag("cart.{$index}.input_value");
            unset($this->cartTotal, $this->roundingTotal);
        } catch (CheckoutValidationException|InsufficientStockException $exception) {
            $this->addError("cart.{$index}.input_value", $exception->getMessage());
        }
    }

    public function removeItem(int $index): void
    {
        $this->authorizeSalesOperator();
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        unset($this->cartTotal, $this->roundingTotal);
    }

    public function clearCart(): void
    {
        $this->authorizeSalesOperator();
        $this->cart = [];
        $this->amountTendered = '';
        $this->resetErrorBag();
        unset($this->cartTotal, $this->roundingTotal, $this->cashChange);
    }

    public function checkout(SalesService $service)
    {
        $this->authorizeSalesOperator();
        $this->validate([
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'amountTendered' => ['nullable', 'decimal:0,2', 'max:9999999999999999999999.99'],
        ]);

        try {
            $sale = $service->checkout($this->cart, $this->paymentMethod, $this->amountTendered ?: null, $this->checkoutKey, auth()->user());

            $route = auth()->user()->isAdministrator() ? 'admin.sales.show' : 'cashier.sales.show';

            return $this->redirectRoute($route, $sale, navigate: true);
        } catch (CheckoutValidationException|InsufficientStockException|ProductPriceChangedException|\ValueError|\InvalidArgumentException $exception) {
            $this->addError('checkout', $exception->getMessage());
        }
    }

    public function render()
    {
        $this->authorizeSalesOperator();

        return view('livewire.pos-terminal');
    }

    private function authorizeSalesOperator(): void
    {
        abort_unless(auth()->check() && auth()->user()->canProcessSales(), 403);
    }
}
