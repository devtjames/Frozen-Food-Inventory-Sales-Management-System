<x-layouts.app title="Sales history">
    <div class="space-y-6">
        <x-page-heading title="Sales history" description="Search completed and reversed transactions, review their saved prices, and reprint receipts." />

        <form method="GET" action="{{ route($routePrefix.'.index') }}" class="card grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-search-field name="search" label="Transaction number" placeholder="SALE-..." :value="$filters['search'] ?? null" />
            <x-input-field name="date_from" label="From date" type="date" :value="$filters['date_from'] ?? null" />
            <x-input-field name="date_to" label="To date" type="date" :value="$filters['date_to'] ?? null" />
            @if($routePrefix === 'admin.sales')
                <x-searchable-dropdown name="cashier" label="Cashier" :options="$cashiers" :selected="$filters['cashier'] ?? null" placeholder="All cashiers" search-placeholder="Search cashiers" />
            @endif
            <x-dropdown name="payment_method" label="Payment method" :options="$paymentMethods" :selected="$filters['payment_method'] ?? null" placeholder="All methods" />
            <x-dropdown name="payment_status" label="Payment status" :options="['paid' => 'Paid', 'refunded' => 'Refunded']" :selected="$filters['payment_status'] ?? null" placeholder="All payment statuses" />
            <x-dropdown name="transaction_status" label="Transaction status" :options="['completed' => 'Completed', 'reversed' => 'Reversed']" :selected="$filters['transaction_status'] ?? null" placeholder="All transaction statuses" />
            <div class="flex items-end gap-2"><x-button type="submit">Filter</x-button>@if(array_filter($filters))<x-button href="{{ route($routePrefix.'.index') }}" variant="secondary">Clear</x-button>@endif</div>
        </form>

        @if($sales->isEmpty())
            <x-empty-state title="No sales found" description="No authorized transactions match the current filters." />
        @else
            <x-responsive-table label="Sales history">
                <x-slot:mobile>
                    @foreach($sales as $sale)
                        <article class="p-4">
                            <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h2 class="truncate font-bold text-slate-950">{{ $sale->transaction_number }}</h2><p class="text-sm text-slate-500">{{ $formatter->transactionTimestamp($sale->sold_at) }}</p></div><x-status-badge :variant="$sale->isReversed() ? 'danger' : 'success'">{{ $sale->effectiveTransactionStatus() }}</x-status-badge></div>
                            <div class="mt-4 grid grid-cols-2 gap-3"><div><p class="text-xs text-slate-500">Total</p><p class="text-lg font-bold text-brand-900">{{ $formatter->money($sale->total_amount) }}</p></div><div><p class="text-xs text-slate-500">Cashier</p><p class="font-semibold">{{ $sale->cashier->name }}</p></div><div><p class="text-xs text-slate-500">Payment</p><p class="font-semibold">{{ $sale->payment_method->label() }}</p></div><div><p class="text-xs text-slate-500">Payment status</p><p class="font-semibold">{{ $sale->effectivePaymentStatus() }}</p></div></div>
                            <div class="mt-4 flex flex-wrap gap-2"><x-button href="{{ route($routePrefix.'.show', $sale) }}" variant="secondary">View</x-button><x-button href="{{ route($routePrefix.'.receipt', $sale) }}" variant="secondary">Receipt</x-button>@if($routePrefix === 'admin.sales' && ! $sale->isReversed())<x-button href="{{ route('admin.sales.reversal.create', $sale) }}" variant="danger">Reverse</x-button>@endif</div>
                        </article>
                    @endforeach
                </x-slot:mobile>
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Transaction</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Cashier</th><th class="px-5 py-3">Total</th><th class="px-5 py-3">Payment</th><th class="px-5 py-3">Payment status</th><th class="px-5 py-3">Transaction status</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach($sales as $sale)
                        <tr><td class="whitespace-nowrap px-5 py-4 font-semibold">{{ $sale->transaction_number }}</td><td class="whitespace-nowrap px-5 py-4">{{ $formatter->transactionTimestamp($sale->sold_at) }}</td><td class="px-5 py-4">{{ $sale->cashier->name }}</td><td class="whitespace-nowrap px-5 py-4 font-bold">{{ $formatter->money($sale->total_amount) }}</td><td class="px-5 py-4">{{ $sale->payment_method->label() }}</td><td class="px-5 py-4"><x-status-badge :variant="$sale->isReversed() ? 'warning' : 'success'">{{ $sale->effectivePaymentStatus() }}</x-status-badge></td><td class="px-5 py-4"><x-status-badge :variant="$sale->isReversed() ? 'danger' : 'success'">{{ $sale->effectiveTransactionStatus() }}</x-status-badge></td><td class="px-5 py-4"><div class="flex justify-end gap-2"><x-button href="{{ route($routePrefix.'.show', $sale) }}" variant="secondary">View</x-button><x-button href="{{ route($routePrefix.'.receipt', $sale) }}" variant="secondary">Receipt</x-button>@if($routePrefix === 'admin.sales' && ! $sale->isReversed())<x-button href="{{ route('admin.sales.reversal.create', $sale) }}" variant="danger">Reverse</x-button>@endif</div></td></tr>
                    @endforeach
                </tbody>
            </x-responsive-table>
            <x-pagination :paginator="$sales" />
        @endif
    </div>
</x-layouts.app>
