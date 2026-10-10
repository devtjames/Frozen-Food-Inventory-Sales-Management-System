<x-layouts.app title="Receive stock">
    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-heading title="Receive new stock" description="Each delivery creates an immutable receipt, independent batch, and stock movement." />
        <x-inventory-nav />
        <x-responsive-card>
            <form method="POST" action="{{ route('admin.inventory.receipts.store') }}" class="space-y-6" x-data="{ submitting: false, quantity: @js(old('quantity_received', '')), cost: @js(old('purchase_cost_per_kg', '')), get total() { const value = Number(this.quantity) * Number(this.cost); return Number.isFinite(value) ? value.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00'; } }" @submit="submitting = true">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
                <x-searchable-dropdown name="product_id" label="Product" :options="$products" :selected="$selectedProduct" placeholder="Select a product" search-placeholder="Search products" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input-field name="quantity_received" label="Quantity received (kg)" type="number" min="0.001" max="999999999.999" step="0.001" inputmode="decimal" required x-model="quantity" help="Enter kilograms, with up to three decimal places." />
                    <x-input-field name="purchase_cost_per_kg" label="Purchase cost per kg (₦)" type="number" min="0.01" max="9999999999.99" step="0.01" inputmode="decimal" required x-model="cost" />
                </div>
                <div class="rounded-2xl border border-brand-100 bg-brand-50 p-4"><p class="text-sm font-medium text-brand-800">Calculated total purchase cost</p><p class="mt-1 text-2xl font-bold text-brand-900">₦<span x-text="total">0.00</span></p><p class="mt-1 text-xs text-brand-800">The server recalculates this exactly before saving.</p></div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input-field name="supplier_name" label="Supplier name" :value="null" maxlength="255" help="Optional." />
                    <x-input-field name="reference_number" label="Reference number" :value="null" maxlength="100" help="Optional invoice, delivery, or internal reference." />
                    <div class="sm:col-span-2"><x-input-field name="received_at" label="Receiving date and time" type="datetime-local" :value="$defaultReceivedAt" required /></div>
                    <div class="sm:col-span-2"><x-textarea-field name="notes" label="Notes" :value="null" rows="4" maxlength="2000" help="Optional delivery notes." /></div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><x-button href="{{ route('admin.inventory.index') }}" variant="secondary">Cancel</x-button><x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Save stock receipt</span><span x-cloak x-show="submitting"><x-loading-indicator label="Receiving stock…" /></span></x-button></div>
            </form>
        </x-responsive-card>
    </div>
</x-layouts.app>
