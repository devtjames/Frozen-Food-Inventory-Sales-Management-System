<x-layouts.app title="Administrator dashboard">
    <div class="space-y-8">
        <x-page-heading title="Administrator dashboard" description="Live operational and financial performance from recorded business activity."><x-slot:actions><x-button href="{{ route('admin.reports.show', 'profit') }}" variant="secondary">Open reports</x-button><x-button href="{{ route('admin.inventory.receipts.create') }}">Receive stock</x-button></x-slot:actions></x-page-heading>

        <section aria-labelledby="today-heading" class="space-y-4"><div><h2 id="today-heading" class="text-xl font-bold text-slate-950">Today at a glance</h2><p class="text-sm text-slate-500">Africa/Lagos business day. Reversed transactions are excluded.</p></div><div class="grid grid-cols-1 gap-4 min-[430px]:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <x-metric-card label="Today's revenue" :value="$formatter->money($today['revenue'])" tone="success" />
            <x-metric-card label="Today's transactions" :value="$today['transactions']" />
            <x-metric-card label="Total available stock" :value="$formatter->kilograms($inventory['available_quantity'])" />
            <x-metric-card label="Low-stock products" :value="$inventory['low_stock_products']" :hint="$inventory['out_of_stock_products'].' out of stock'" tone="warning" />
            <x-metric-card label="Total stock value" :value="$formatter->money($inventory['stock_value'])" />
            <x-metric-card label="Today's gross profit" :value="$formatter->money($today['gross_profit'])" hint="Revenue minus FIFO cost of goods sold" tone="success" />
            <x-metric-card label="Recorded expenses" :value="$formatter->money($today['expenses'])" hint="Today's operating expenses" />
            <x-metric-card label="Recorded stock losses" :value="$formatter->money($today['inventory_losses'])" hint="Recognized separately from COGS" tone="danger" />
            <x-metric-card label="Estimated net profit" :value="$formatter->money($today['net_profit'])" hint="Gross profit minus expenses and inventory losses" :tone="str_starts_with($today['net_profit'], '-') ? 'danger' : 'success'" />
        </div></section>

        <section aria-labelledby="stock-warnings-heading" class="space-y-4">
            <div class="flex items-end justify-between gap-4"><div><h2 id="stock-warnings-heading" class="text-xl font-bold text-slate-950">Stock warnings</h2><p class="text-sm text-slate-500">Live balances from remaining stock batches.</p></div><a href="{{ route('admin.notifications.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">View notifications</a></div>
            @if($stockWarnings->isEmpty())
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">All active products are above their low-stock thresholds.</div>
            @else
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($stockWarnings as $product)
                        <a href="{{ route('admin.products.show', $product) }}" class="rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-sm {{ $product->computed_stock_alert_state === \App\Enums\StockAlertState::OutOfStock ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50' }}">
                            <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-slate-950">{{ $product->name }}</p><p class="text-sm text-slate-600">{{ $product->category->name }}</p></div><x-status-badge :variant="$product->computed_stock_alert_state === \App\Enums\StockAlertState::OutOfStock ? 'danger' : 'warning'">{{ $product->computed_stock_alert_state->label() }}</x-status-badge></div>
                            <p class="mt-4 text-lg font-bold text-slate-950">{{ $formatter->kilograms($product->available_quantity) }}</p><p class="text-xs text-slate-500">Threshold: {{ $formatter->kilograms($product->low_stock_threshold) }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="space-y-5"><div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"><div><h2 class="text-xl font-bold text-slate-950">Sales analytics</h2><p class="text-sm text-slate-500">{{ $chartPeriod->label }} · valid, non-reversed sales</p></div><form method="GET" action="{{ route('admin.dashboard') }}" x-data="{ period: @js($filters['period'] ?? 'this_month') }" class="card grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4"><x-dropdown name="period" label="Chart period" :options="['today' => 'Today', 'yesterday' => 'Yesterday', 'this_week' => 'This week', 'this_month' => 'This month', 'custom' => 'Custom range']" :selected="$filters['period'] ?? 'this_month'" x-model="period" /><div x-show="period === 'custom'"><x-input-field name="date_from" label="From" type="date" :value="$filters['date_from'] ?? null" /></div><div x-show="period === 'custom'"><x-input-field name="date_to" label="To" type="date" :value="$filters['date_to'] ?? null" /></div><div class="flex items-end"><x-button type="submit" class="w-full">Apply</x-button></div></form></div>
            <div class="grid gap-5 lg:grid-cols-2"><x-sales-chart id="daily-sales-chart" title="Daily sales" :data="$charts['daily']" type="line" /><x-sales-chart id="weekly-sales-chart" title="Weekly sales" :data="$charts['weekly']" /><x-sales-chart id="monthly-sales-chart" title="Monthly sales" :data="$charts['monthly']" /><x-sales-chart id="product-sales-chart" title="Sales by product" :data="$charts['products']" /><x-sales-chart id="category-sales-chart" title="Sales by category" :data="$charts['categories']" /><x-sales-chart id="cashier-sales-chart" title="Sales by cashier" :data="$charts['cashiers']" /></div>
        </section>

        <x-responsive-card title="Recent transactions">@if($recentSales->isEmpty())<p class="text-sm text-slate-500">No transactions have been recorded.</p>@else<div class="space-y-3">@foreach($recentSales as $sale)<a href="{{ route('admin.sales.show', $sale) }}" class="flex min-h-14 items-center justify-between gap-4 rounded-xl border border-slate-200 px-4 py-3 hover:bg-slate-50"><div class="min-w-0"><p class="truncate font-semibold">{{ $sale->transaction_number }}</p><p class="text-xs text-slate-500">{{ $sale->cashier->name }} · {{ $formatter->transactionTimestamp($sale->sold_at) }}</p></div><div class="text-right"><p class="font-bold">{{ $formatter->money($sale->total_amount) }}</p><p class="text-xs {{ $sale->isReversed() ? 'text-red-700' : 'text-emerald-700' }}">{{ $sale->effectiveTransactionStatus() }}</p></div></a>@endforeach</div>@endif</x-responsive-card>
    </div>
</x-layouts.app>
