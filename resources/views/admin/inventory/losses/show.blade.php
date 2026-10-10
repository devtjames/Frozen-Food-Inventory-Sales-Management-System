<x-layouts.app title="Stock loss">
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-heading title="Stock loss #{{ $loss->id }}" description="Immutable inventory loss for {{ $loss->product->name }}.">
            <x-slot:actions><x-button href="{{ route('admin.inventory.losses.index') }}" variant="secondary">Loss history</x-button>@unless($loss->isReversed())<x-button href="{{ route('admin.inventory.losses.reversal.create', $loss) }}" variant="danger">Reverse loss</x-button>@endunless</x-slot:actions>
        </x-page-heading>
        <x-inventory-nav />
        @error('reversal')<x-notification type="error" :message="$message" />@enderror

        <div class="grid gap-6 lg:grid-cols-2">
            <x-responsive-card title="Loss details">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm text-slate-500">Product</dt><dd class="font-semibold">{{ $loss->product->name }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Category</dt><dd class="font-semibold">{{ $loss->category->label() }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Quantity lost</dt><dd class="text-xl font-bold text-red-700">{{ $formatter->kilograms($loss->quantity_lost) }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Financial loss</dt><dd class="text-xl font-bold">{{ $formatter->money($loss->financial_value) }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Current availability</dt><dd class="font-bold text-brand-800">{{ $formatter->kilograms($availableQuantity) }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Status</dt><dd><x-status-badge :variant="$loss->isReversed() ? 'neutral' : 'warning'">{{ $loss->isReversed() ? 'Reversed' : 'Recorded' }}</x-status-badge></dd></div>
                    <div><dt class="text-sm text-slate-500">Loss date</dt><dd class="font-semibold">{{ $formatter->transactionTimestamp($loss->lost_at) }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Recorded by</dt><dd class="font-semibold">{{ $loss->recorder->name }}</dd></div>
                </dl>
            </x-responsive-card>
            <x-responsive-card title="Reason and description"><p class="font-semibold">{{ $loss->reason }}</p><p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $loss->description ?: 'No additional description was provided.' }}</p></x-responsive-card>
        </div>

        <x-responsive-card title="Batch cost allocations">
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b border-slate-200 text-slate-500"><th class="py-2 pr-4">Batch</th><th class="py-2 pr-4">Quantity</th><th class="py-2 pr-4">Purchase cost/kg</th><th class="py-2">Financial value</th></tr></thead><tbody>@foreach($loss->allocations as $allocation)<tr class="border-b border-slate-100"><td class="py-3 pr-4"><a class="font-semibold text-brand-700 hover:underline" href="{{ route('admin.inventory.batches.show', $allocation->batch) }}">#{{ $allocation->stock_batch_id }}</a></td><td class="py-3 pr-4">{{ $formatter->kilograms($allocation->quantity) }}</td><td class="py-3 pr-4">{{ $formatter->money($allocation->purchase_cost_per_kg) }}</td><td class="py-3 font-semibold">{{ $formatter->money($allocation->total_cost) }}</td></tr>@endforeach</tbody></table></div>
        </x-responsive-card>

        @if($loss->reversal)
            <x-responsive-card title="Reversal"><dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-sm text-slate-500">Reversed by</dt><dd class="font-semibold">{{ $loss->reversal->recorder->name }}</dd></div><div><dt class="text-sm text-slate-500">Reversal date</dt><dd class="font-semibold">{{ $formatter->transactionTimestamp($loss->reversal->reversed_at) }}</dd></div><div class="sm:col-span-2"><dt class="text-sm text-slate-500">Reason</dt><dd class="mt-1 whitespace-pre-line">{{ $loss->reversal->reason }}</dd></div></dl></x-responsive-card>
        @endif
    </div>
</x-layouts.app>
