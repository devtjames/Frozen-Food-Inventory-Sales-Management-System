<x-layouts.app title="Stock adjustment">
    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-heading title="New stock adjustment" description="Record a separate, auditable correction without editing receipts or previous movements." />
        <x-inventory-nav />
        <x-responsive-card>
            <form method="POST" action="{{ route('admin.inventory.adjustments.store') }}" class="space-y-6" x-data="{ submitting: false, type: @js(old('type', 'positive')), quantity: @js(old('quantity', '')), cost: @js(old('cost_basis_per_kg', '')), get total() { const value = Number(this.quantity) * Number(this.cost); return Number.isFinite(value) ? value.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00'; } }" @submit="submitting = true">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
                <x-searchable-dropdown name="product_id" label="Product" :options="$products" :selected="$selectedProduct" placeholder="Select a product" search-placeholder="Search products" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><label for="type" class="form-label">Adjustment type <span class="text-red-600">*</span></label><select id="type" name="type" x-model="type" class="form-control" required><option value="positive">Positive correction</option><option value="negative">Negative correction</option></select>@error('type')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <x-input-field name="quantity" label="Quantity (kg)" type="number" min="0.001" max="999999999.999" step="0.001" inputmode="decimal" required x-model="quantity" />
                    <div x-show="type === 'positive'" class="sm:col-span-2"><x-input-field name="cost_basis_per_kg" label="Cost basis per kg (₦)" type="number" min="0.01" max="9999999999.99" step="0.01" inputmode="decimal" x-bind:required="type === 'positive'" x-bind:disabled="type !== 'positive'" x-model="cost" help="Required for positive corrections so the new batch has an auditable cost basis." /></div>
                </div>
                <div x-show="type === 'positive'" class="rounded-xl border border-brand-100 bg-brand-50 p-4"><p class="text-sm font-medium text-brand-800">Calculated cost impact</p><p class="mt-1 text-xl font-bold text-brand-900">₦<span x-text="total">0.00</span></p></div>
                <div x-show="type === 'negative'" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">The quantity will be deducted from the oldest available batches using FIFO. The saved adjustment will preserve every batch allocation and purchase cost.</div>
                <x-textarea-field name="reason" label="Reason" :value="null" rows="4" maxlength="2000" required help="Required. Explain why this correction is necessary." />
                <div class="grid gap-5 sm:grid-cols-2"><x-input-field name="adjusted_at" label="Adjustment date and time" type="datetime-local" :value="$defaultAdjustedAt" required /><x-input-field name="reference_number" label="Reference number" :value="null" maxlength="100" help="Optional internal reference." /></div>
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><x-button href="{{ route('admin.inventory.adjustments.index') }}" variant="secondary">Cancel</x-button><x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Record adjustment</span><span x-cloak x-show="submitting"><x-loading-indicator label="Recording…" /></span></x-button></div>
            </form>
        </x-responsive-card>
    </div>
</x-layouts.app>
