<x-layouts.app title="Sale details">
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-heading title="Sale details" description="{{ $sale->transaction_number }}">
            <x-slot:actions><x-button href="{{ route($routePrefix.'.index') }}" variant="secondary">Sales history</x-button><x-button href="{{ route($routePrefix.'.receipt', $sale) }}">Print receipt</x-button>@if($routePrefix === 'admin.sales' && ! $sale->isReversed())<x-button href="{{ route('admin.sales.reversal.create', $sale) }}" variant="danger">Reverse sale</x-button>@endif</x-slot:actions>
        </x-page-heading>

        @error('reversal')<x-notification variant="danger" :message="$message" />@enderror

        <x-responsive-card title="Transaction summary">
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-sm text-slate-500">Receipt number</dt><dd class="break-all font-semibold">{{ $sale->transaction_number }}</dd></div>
                <div><dt class="text-sm text-slate-500">Cashier</dt><dd class="font-semibold">{{ $sale->cashier->name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Date and time</dt><dd class="font-semibold">{{ $formatter->transactionTimestamp($sale->sold_at) }}</dd></div>
                <div><dt class="text-sm text-slate-500">Payment method</dt><dd class="font-semibold">{{ $sale->payment_method->label() }}</dd></div>
                <div><dt class="text-sm text-slate-500">Payment status</dt><dd><x-status-badge :variant="$sale->isReversed() ? 'warning' : 'success'">{{ $sale->effectivePaymentStatus() }}</x-status-badge></dd></div>
                <div><dt class="text-sm text-slate-500">Transaction status</dt><dd><x-status-badge :variant="$sale->isReversed() ? 'danger' : 'success'">{{ $sale->effectiveTransactionStatus() }}</x-status-badge></dd></div>
                @if($sale->amount_tendered !== null)<div><dt class="text-sm text-slate-500">Cash tendered</dt><dd class="font-semibold">{{ $formatter->money($sale->amount_tendered) }}</dd></div><div><dt class="text-sm text-slate-500">Change given</dt><dd class="font-semibold">{{ $formatter->money($sale->change_due) }}</dd></div>@endif
            </dl>
        </x-responsive-card>

        <x-responsive-table label="Products sold">
            <x-slot:mobile>@foreach($sale->items as $item)<article class="p-4"><div class="flex justify-between gap-3"><div><h2 class="font-bold">{{ $item->product_name }}</h2><p class="text-sm text-slate-500">{{ $formatter->kilograms($item->quantity) }} × {{ $formatter->money($item->selling_price_per_kg) }}/kg</p></div><p class="font-bold">{{ $formatter->money($item->item_amount) }}</p></div>@if($item->rounding_adjustment !== '0.00')<p class="mt-2 text-xs text-amber-700">Rounding adjustment: {{ $formatter->money($item->rounding_adjustment) }}</p>@endif</article>@endforeach</x-slot:mobile>
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Quantity</th><th class="px-5 py-3">Selling price / kg</th><th class="px-5 py-3">Rounding</th><th class="px-5 py-3 text-right">Item amount</th></tr></thead>
            <tbody class="divide-y divide-slate-200 bg-white">@foreach($sale->items as $item)<tr><td class="px-5 py-4 font-semibold">{{ $item->product_name }}</td><td class="whitespace-nowrap px-5 py-4">{{ $formatter->kilograms($item->quantity) }}</td><td class="whitespace-nowrap px-5 py-4">{{ $formatter->money($item->selling_price_per_kg) }}</td><td class="whitespace-nowrap px-5 py-4">{{ $formatter->money($item->rounding_adjustment) }}</td><td class="whitespace-nowrap px-5 py-4 text-right font-bold">{{ $formatter->money($item->item_amount) }}</td></tr>@endforeach</tbody>
        </x-responsive-table>

        <div class="card ml-auto max-w-md p-5"><dl class="space-y-3"><div class="flex justify-between gap-4"><dt class="text-slate-600">Rounding adjustment</dt><dd class="font-semibold">{{ $formatter->money($sale->total_rounding_adjustment) }}</dd></div><div class="flex justify-between gap-4 border-t border-slate-200 pt-3 text-xl"><dt class="font-bold">Total</dt><dd class="font-bold text-brand-900">{{ $formatter->money($sale->total_amount) }}</dd></div></dl></div>

        @if($sale->reversal)
            <x-responsive-card title="Reversal and refund audit"><dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-sm text-slate-500">Responsible administrator</dt><dd class="font-semibold">{{ $sale->reversal->administrator->name }}</dd></div><div><dt class="text-sm text-slate-500">Reversal date</dt><dd class="font-semibold">{{ $formatter->transactionTimestamp($sale->reversal->reversed_at) }}</dd></div><div><dt class="text-sm text-slate-500">Stock treatment</dt><dd class="font-semibold">{{ $sale->reversal->stock_action->label() }}</dd></div><div><dt class="text-sm text-slate-500">Refund amount and method</dt><dd class="font-semibold">{{ $formatter->money($sale->reversal->refund->amount) }} via {{ $sale->reversal->refund->method->label() }}</dd></div><div><dt class="text-sm text-slate-500">Refund date</dt><dd class="font-semibold">{{ $formatter->transactionTimestamp($sale->reversal->refund->refunded_at) }}</dd></div><div><dt class="text-sm text-slate-500">Refund status</dt><dd><x-status-badge variant="success">{{ $sale->reversal->refund->status->label() }}</x-status-badge></dd></div><div class="sm:col-span-2"><dt class="text-sm text-slate-500">Reason</dt><dd class="whitespace-pre-line font-semibold">{{ $sale->reversal->reason }}</dd></div></dl></x-responsive-card>
        @endif
    </div>
</x-layouts.app>
