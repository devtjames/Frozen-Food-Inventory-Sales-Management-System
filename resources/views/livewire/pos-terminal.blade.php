@php($formatter = app(\App\Services\BusinessFormatter::class))
<div class="space-y-5">
    <x-page-heading title="Point of sale" description="Sell by customer amount or kilograms. All calculations are verified again during checkout." />

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(22rem,0.65fr)] xl:items-start">
        <section class="space-y-4">
            <div class="card grid gap-3 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(11rem,0.35fr)]">
                <x-search-field name="pos_search" label="Search products" placeholder="Search product name" wire:model.live.debounce.250ms="search" />
                <div><label for="pos-category" class="form-label">Category</label><select id="pos-category" class="form-control" wire:model.live="category"><option value="">All categories</option>@foreach($this->categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
            </div>

            <div wire:loading.delay class="rounded-xl border border-brand-100 bg-brand-50 p-3 text-sm font-semibold text-brand-800">Updating products…</div>

            @if($this->products->isEmpty())
                <x-empty-state title="No sellable products found" description="Change the search or ask an administrator to confirm product status and stock." />
            @else
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" aria-label="Sellable products">
                    @foreach($this->products as $product)
                        <button type="button" wire:key="product-{{ $product->id }}" wire:click="selectProduct({{ $product->id }})" class="min-h-32 rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:border-brand-400 hover:shadow {{ (string) $selectedProductId === (string) $product->id ? 'border-brand-700 ring-2 ring-brand-100' : 'border-slate-200' }}">
                            <span class="block font-bold text-slate-950">{{ $product->name }}</span><span class="mt-0.5 block text-xs text-slate-500">{{ $product->category->name }}</span>
                            <span class="mt-4 flex items-end justify-between gap-2"><span class="text-lg font-bold text-brand-800">{{ $formatter->money($product->selling_price) }}<span class="text-xs font-medium">/kg</span></span><span class="text-xs font-semibold text-slate-600">{{ $formatter->kilograms($product->available_quantity) }}</span></span>
                        </button>
                    @endforeach
                </div>
            @endif

            @if($this->selectedProduct)
                <x-responsive-card title="Add {{ $this->selectedProduct->name }}">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl bg-brand-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Selling price</p><p class="mt-1 text-2xl font-bold text-brand-950">{{ $formatter->money($this->selectedProduct->selling_price) }}/kg</p><p class="mt-1 text-sm text-brand-800">Available: {{ $formatter->kilograms($this->selectedProduct->available_quantity) }}</p></div>
                        <div><label class="form-label" for="selling-method">Selling method</label><select id="selling-method" wire:model.live="sellingMethod" class="form-control"><option value="amount">By customer amount</option><option value="kilograms">By kilograms</option></select></div>
                        <div class="sm:col-span-2"><label class="form-label" for="sale-input">{{ $sellingMethod === 'amount' ? 'Customer amount (₦)' : 'Quantity (kg)' }}</label><input id="sale-input" type="number" min="{{ $sellingMethod === 'amount' ? '0.01' : '0.001' }}" step="{{ $sellingMethod === 'amount' ? '0.01' : '0.001' }}" inputmode="decimal" wire:model.live.debounce.250ms="inputValue" class="form-control text-xl font-bold" placeholder="{{ $sellingMethod === 'amount' ? '0.00' : '0.000' }}">@error('inputValue')<p class="form-error">{{ $message }}</p>@enderror @error('selectedProductId')<p class="form-error">{{ $message }}</p>@enderror</div>
                    </div>

                    @if($this->preview)
                        <div class="mt-5 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-3"><div><p class="text-xs text-slate-500">Quantity</p><p class="text-lg font-bold">{{ $formatter->kilograms($this->preview['quantity']) }}</p></div><div><p class="text-xs text-slate-500">Payable amount</p><p class="text-lg font-bold">{{ $formatter->money($this->preview['item_amount']) }}</p></div><div><p class="text-xs text-slate-500">Rounding adjustment</p><p class="text-lg font-bold">{{ $formatter->money($this->preview['rounding_adjustment']) }}</p></div></div>
                    @endif

                    <div class="mt-5 flex justify-end"><x-button type="button" wire:click="addItem" wire:loading.attr="disabled" wire:target="addItem"><span wire:loading.remove wire:target="addItem">Add to cart</span><span wire:loading wire:target="addItem"><x-loading-indicator label="Adding…" /></span></x-button></div>
                </x-responsive-card>
            @endif
        </section>

        <aside class="space-y-4 xl:sticky xl:top-24">
            <x-responsive-card title="Shopping cart">
                @if($cart === [])
                    <x-empty-state title="Cart is empty" description="Select a product and enter an amount or quantity." />
                @else
                    <div class="space-y-4">
                        @foreach($cart as $index => $item)
                            <article wire:key="cart-{{ $item['line_id'] }}" class="rounded-xl border border-slate-200 p-3">
                                <div class="flex items-start justify-between gap-3"><div><h3 class="font-bold">{{ $item['product_name'] }}</h3><p class="text-xs text-slate-500">{{ $item['selling_method'] === 'amount' ? 'By amount' : 'By kilograms' }} · {{ $formatter->money($item['selling_price_per_kg']) }}/kg</p></div><button type="button" wire:click="removeItem({{ $index }})" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-red-700 hover:bg-red-50">Remove</button></div>
                                <div class="mt-3 flex gap-2"><div class="min-w-0 flex-1"><label for="cart-input-{{ $index }}" class="form-label">{{ $item['selling_method'] === 'amount' ? 'Amount (₦)' : 'Quantity (kg)' }}</label><input id="cart-input-{{ $index }}" type="number" step="{{ $item['selling_method'] === 'amount' ? '0.01' : '0.001' }}" wire:model="cart.{{ $index }}.input_value" class="form-control"></div><div class="flex items-end"><x-button type="button" variant="secondary" wire:click="updateItem({{ $index }})">Update</x-button></div></div>
                                @error("cart.{$index}.input_value")<p class="form-error">{{ $message }}</p>@enderror
                                <div class="mt-3 grid grid-cols-2 gap-2 text-sm"><div><span class="text-slate-500">Quantity</span><p class="font-bold">{{ $formatter->kilograms($item['quantity']) }}</p></div><div class="text-right"><span class="text-slate-500">Amount</span><p class="font-bold">{{ $formatter->money($item['item_amount']) }}</p></div></div>
                                @if($item['rounding_adjustment'] !== '0.00')<p class="mt-2 text-xs text-amber-700">Weight rounding adjustment: {{ $formatter->money($item['rounding_adjustment']) }}</p>@endif
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-5 border-t border-slate-200 pt-4"><div class="flex items-center justify-between"><span class="font-semibold text-slate-600">Total</span><span class="text-2xl font-bold text-brand-900">{{ $formatter->money($this->cartTotal) }}</span></div>@if($this->roundingTotal !== '0.00')<div class="mt-1 flex justify-between text-xs text-slate-500"><span>Rounding reconciliation</span><span>{{ $formatter->money($this->roundingTotal) }}</span></div>@endif</div>
                    <div class="mt-4 grid gap-4"><x-dropdown name="payment_method_display" label="Payment method" :options="['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'card' => 'POS/Card']" :selected="$paymentMethod" wire:model.live="paymentMethod" required />@if($paymentMethod === 'cash')<div><label for="amount-tendered" class="form-label">Cash tendered (optional)</label><input id="amount-tendered" type="number" min="0" step="0.01" inputmode="decimal" wire:model.live.debounce.250ms="amountTendered" class="form-control text-lg font-bold">@error('amountTendered')<p class="form-error">{{ $message }}</p>@enderror @if($this->cashChange !== null)<p class="mt-2 font-semibold text-emerald-700">Change: {{ $formatter->money($this->cashChange) }}</p>@endif</div>@endif</div>
                    @error('checkout')<x-notification type="error" class="mt-4" :message="$message" />@enderror
                    <div class="mt-5 grid gap-2">
                        <x-confirmation-dialog name="confirm-checkout" title="Confirm paid sale" confirm-text="Complete sale">
                            <x-slot:trigger>
                                <x-button type="button" class="w-full text-base" @click="$dispatch('open-confirmation', 'confirm-checkout')">
                                    Confirm sale · {{ $formatter->money($this->cartTotal) }}
                                </x-button>
                            </x-slot:trigger>

                            Confirm payment has been received. This will deduct stock immediately and cannot be edited by the cashier.

                            <x-slot:confirm>
                                <x-button type="button" wire:click="checkout" wire:loading.attr="disabled" wire:target="checkout">
                                    <span wire:loading.remove wire:target="checkout">Complete paid sale</span>
                                    <span wire:loading wire:target="checkout"><x-loading-indicator label="Processing…" /></span>
                                </x-button>
                            </x-slot:confirm>
                        </x-confirmation-dialog>
                        <x-button type="button" variant="ghost" wire:click="clearCart">Clear cart</x-button>
                    </div>
                @endif
            </x-responsive-card>
        </aside>
    </div>
</div>
