<x-layouts.app title="Record stock loss">
    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-heading title="Record stock loss" description="Deduct spoiled, damaged, missing, or unusable stock without recording a sale." />
        <x-inventory-nav />

        <x-responsive-card>
            <form method="POST" action="{{ route('admin.inventory.losses.store') }}" id="stock-loss-form" class="space-y-6"
                x-data="{
                    submitting: false,
                    productId: @js((string) old('product_id', $selectedProduct)),
                    batchId: @js((string) old('stock_batch_id', '')),
                    quantity: @js((string) old('quantity_lost', '')),
                    balances: @js($productBalances),
                    batches: @js($batchOptions),
                    get available() { return Number(this.balances[this.productId] || 0); },
                    get resulting() { return this.available - Number(this.quantity || 0); },
                    get eligibleBatches() { return this.batches.filter(batch => batch.product_id === this.productId); },
                    kilograms(value) { return `${Number(value || 0).toLocaleString('en-NG', { maximumFractionDigits: 3 })} kg`; }
                }"
                @submit="submitting = true">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

                <x-searchable-dropdown name="product_id" label="Product" :options="$productOptions" :selected="$selectedProduct" placeholder="Select a product" search-placeholder="Search products" required x-model="productId" @change="batchId = ''" />

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-brand-100 bg-brand-50 p-4">
                        <p class="text-sm font-medium text-brand-800">Available stock</p>
                        <p class="mt-1 text-xl font-bold text-brand-950" x-text="kilograms(available)">0 kg</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-medium text-slate-600">Quantity lost</p>
                        <p class="mt-1 text-xl font-bold text-slate-950" x-text="kilograms(quantity)">0 kg</p>
                    </div>
                    <div class="rounded-xl border p-4" :class="resulting < 0 ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50'">
                        <p class="text-sm font-medium" :class="resulting < 0 ? 'text-red-800' : 'text-emerald-800'">Resulting balance</p>
                        <p class="mt-1 text-xl font-bold" :class="resulting < 0 ? 'text-red-950' : 'text-emerald-950'" x-text="kilograms(resulting)">0 kg</p>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-dropdown name="category" label="Loss category" :options="$categories" :selected="old('category')" placeholder="Select a loss category" required />
                    <x-input-field name="quantity_lost" label="Quantity lost (kg)" type="number" min="0.001" max="999999999.999" step="0.001" inputmode="decimal" required x-model="quantity" help="Enter kilograms with up to three decimal places." />
                </div>

                <div>
                    <label for="stock_batch_id" class="form-label">Affected batch</label>
                    <select id="stock_batch_id" name="stock_batch_id" class="form-control" x-model="batchId">
                        <option value="">Unknown — allocate using FIFO</option>
                        <template x-for="batch in eligibleBatches" :key="batch.id">
                            <option :value="batch.id" x-text="batch.label"></option>
                        </template>
                    </select>
                    @error('stock_batch_id')<p class="form-error">{{ $message }}</p>@enderror
                    <p class="mt-1.5 text-sm text-slate-500">Choose the actual affected batch when known. Otherwise the oldest eligible stock is used.</p>
                </div>

                <x-input-field name="reason" label="Reason" :value="null" maxlength="255" required help="A concise explanation for the loss." />
                <x-textarea-field name="description" label="Description" :value="null" rows="4" maxlength="2000" help="Optional supporting details." />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input-field name="lost_at" label="Loss date and time" type="datetime-local" :value="$defaultLostAt" required />
                    <div><span class="form-label">Recorded by</span><div class="form-control flex items-center bg-slate-50 text-slate-700">{{ auth()->user()->name }}</div></div>
                </div>

                <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-900">Financial loss is calculated after allocation from each batch's purchase cost. Selling prices are never used.</div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <x-button href="{{ route('admin.inventory.losses.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Record stock loss</span><span x-cloak x-show="submitting"><x-loading-indicator label="Recording loss…" /></span></x-button>
                </div>
            </form>
        </x-responsive-card>
    </div>
</x-layouts.app>
