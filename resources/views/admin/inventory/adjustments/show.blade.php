<x-layouts.app title="Stock adjustment">
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-heading title="Stock adjustment #{{ $adjustment->id }}" description="Immutable {{ Str::lower($adjustment->type->label()) }} for {{ $adjustment->product->name }}.">
            <x-slot:actions><x-button href="{{ route('admin.inventory.adjustments.index') }}" variant="secondary">Adjustments</x-button></x-slot:actions>
        </x-page-heading>
        <x-inventory-nav />
        <div class="grid gap-6 lg:grid-cols-2">
            <x-responsive-card title="Adjustment"><dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-sm text-slate-500">Product</dt><dd class="font-semibold">{{ $adjustment->product->name }}</dd></div><div><dt class="text-sm text-slate-500">Type</dt><dd><x-status-badge :variant="$adjustment->type->value === 'positive' ? 'success' : 'warning'">{{ $adjustment->type->label() }}</x-status-badge></dd></div><div><dt class="text-sm text-slate-500">Quantity</dt><dd class="font-semibold">{{ $formatter->kilograms($adjustment->quantity) }}</dd></div><div><dt class="text-sm text-slate-500">Cost impact</dt><dd class="font-semibold">{{ $formatter->money($adjustment->total_cost_impact) }}</dd></div><div><dt class="text-sm text-slate-500">Cost basis per kg</dt><dd class="font-semibold">{{ $adjustment->cost_basis_per_kg ? $formatter->money($adjustment->cost_basis_per_kg) : 'Allocated by FIFO' }}</dd></div><div><dt class="text-sm text-slate-500">Current availability</dt><dd class="font-semibold text-brand-800">{{ $formatter->kilograms($availableQuantity) }}</dd></div><div><dt class="text-sm text-slate-500">Recorded by</dt><dd class="font-semibold">{{ $adjustment->recorder->name }}</dd></div><div><dt class="text-sm text-slate-500">Date</dt><dd class="font-semibold">{{ $formatter->transactionTimestamp($adjustment->adjusted_at) }}</dd></div></dl></x-responsive-card>
            <x-responsive-card title="Reason"><p class="whitespace-pre-line text-sm leading-6">{{ $adjustment->reason }}</p></x-responsive-card>
        </div>
        @if($adjustment->batch)
            <x-responsive-card title="Created batch"><a href="{{ route('admin.inventory.batches.show', $adjustment->batch) }}" class="font-semibold text-brand-700 hover:underline">Batch #{{ $adjustment->batch->id }}</a><p class="mt-2 text-sm text-slate-600">Remaining: {{ $formatter->kilograms($adjustment->batch->remaining_quantity) }}</p></x-responsive-card>
        @endif
        @if($adjustment->allocations->isNotEmpty())
            <x-responsive-card title="FIFO allocations"><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b border-slate-200 text-slate-500"><th class="py-2 pr-4">Batch</th><th class="py-2 pr-4">Quantity</th><th class="py-2 pr-4">Cost/kg</th><th class="py-2">Allocated cost</th></tr></thead><tbody>@foreach($adjustment->allocations as $allocation)<tr class="border-b border-slate-100"><td class="py-3 pr-4"><a class="text-brand-700 hover:underline" href="{{ route('admin.inventory.batches.show', $allocation->batch) }}">#{{ $allocation->stock_batch_id }}</a></td><td class="py-3 pr-4">{{ $formatter->kilograms($allocation->quantity) }}</td><td class="py-3 pr-4">{{ $formatter->money($allocation->purchase_cost_per_kg) }}</td><td class="py-3">{{ $formatter->money($allocation->total_cost) }}</td></tr>@endforeach</tbody></table></div></x-responsive-card>
        @endif
    </div>
</x-layouts.app>
