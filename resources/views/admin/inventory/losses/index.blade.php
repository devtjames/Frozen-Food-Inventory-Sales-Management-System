<x-layouts.app title="Stock losses">
    <div class="space-y-6">
        <x-page-heading title="Stock loss history" description="Spoiled, damaged, missing, and otherwise unusable inventory with purchase-cost valuation.">
            <x-slot:actions><x-button href="{{ route('admin.inventory.losses.create') }}">Record stock loss</x-button></x-slot:actions>
        </x-page-heading>
        <x-inventory-nav />

        <form method="GET" action="{{ route('admin.inventory.losses.index') }}" class="card p-4 sm:p-5">
            <div class="mb-4 border-b border-slate-200 pb-3"><h2 class="font-bold text-slate-950">Find stock losses</h2><p class="mt-1 text-sm text-slate-500">Search the history, then narrow it by product, category, status, or date.</p></div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-12 xl:items-end">
                <div class="xl:col-span-4"><x-search-field name="search" label="Search records" placeholder="Product, reason, or description" :value="$filters['search'] ?? null" /></div>
                <div class="xl:col-span-4"><x-searchable-dropdown name="product" label="Product" :options="$products" :selected="$filters['product'] ?? null" placeholder="All products" search-placeholder="Search or select a product" /></div>
                <div class="xl:col-span-2"><x-dropdown name="category" label="Loss category" :options="$categories" :selected="$filters['category'] ?? null" placeholder="All categories" /></div>
                <div class="xl:col-span-2"><x-dropdown name="status" label="Status" :options="['active' => 'Recorded', 'reversed' => 'Reversed']" :selected="$filters['status'] ?? null" placeholder="All statuses" /></div>
                <div class="xl:col-span-3"><x-input-field name="date_from" label="From date" type="date" :value="$filters['date_from'] ?? null" /></div>
                <div class="xl:col-span-3"><x-input-field name="date_to" label="To date" type="date" :value="$filters['date_to'] ?? null" /></div>
                <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-6 xl:justify-end"><x-button type="submit">Apply filters</x-button>@if(array_filter($filters))<x-button href="{{ route('admin.inventory.losses.index') }}" variant="secondary">Clear filters</x-button>@endif</div>
            </div>
        </form>

        @if($losses->isEmpty())
            <x-empty-state title="No stock losses found" description="Record a loss or change the current filters." />
        @else
            <x-responsive-table label="Stock losses">
                <x-slot:mobile>
                    @foreach($losses as $loss)
                        <article class="p-4">
                            <div class="flex items-start justify-between gap-3"><div><h2 class="font-bold text-slate-950">{{ $loss->product->name }}</h2><p class="text-sm text-slate-500">{{ $loss->category->label() }}</p></div><x-status-badge :variant="$loss->isReversed() ? 'neutral' : 'warning'">{{ $loss->isReversed() ? 'Reversed' : 'Recorded' }}</x-status-badge></div>
                            <div class="mt-4 grid grid-cols-2 gap-3"><div><p class="text-xs text-slate-500">Quantity lost</p><p class="font-bold text-red-700">{{ $formatter->kilograms($loss->quantity_lost) }}</p></div><div><p class="text-xs text-slate-500">Financial loss</p><p class="font-bold">{{ $formatter->money($loss->financial_value) }}</p></div></div>
                            <p class="mt-3 text-sm text-slate-600">{{ $loss->reason }}</p><p class="mt-1 text-xs text-slate-500">{{ $formatter->transactionTimestamp($loss->lost_at) }} · {{ $loss->recorder->name }}</p>
                            <div class="mt-4 flex gap-2"><x-button href="{{ route('admin.inventory.losses.show', $loss) }}" variant="secondary">View</x-button>@unless($loss->isReversed())<x-button href="{{ route('admin.inventory.losses.reversal.create', $loss) }}" variant="danger">Reverse</x-button>@endunless</div>
                        </article>
                    @endforeach
                </x-slot:mobile>
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Quantity</th><th class="px-5 py-3">Financial value</th><th class="px-5 py-3">Reason</th><th class="px-5 py-3">Date / recorder</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach($losses as $loss)
                        <tr><td class="px-5 py-4 font-semibold">{{ $loss->product->name }}</td><td class="px-5 py-4">{{ $loss->category->label() }}</td><td class="px-5 py-4 font-bold text-red-700">{{ $formatter->kilograms($loss->quantity_lost) }}</td><td class="px-5 py-4 font-semibold">{{ $formatter->money($loss->financial_value) }}</td><td class="max-w-56 px-5 py-4"><p class="truncate">{{ $loss->reason }}</p></td><td class="whitespace-nowrap px-5 py-4"><p>{{ $formatter->transactionTimestamp($loss->lost_at) }}</p><p class="text-xs text-slate-500">{{ $loss->recorder->name }}</p></td><td class="px-5 py-4"><x-status-badge :variant="$loss->isReversed() ? 'neutral' : 'warning'">{{ $loss->isReversed() ? 'Reversed' : 'Recorded' }}</x-status-badge></td><td class="px-5 py-4"><div class="flex justify-end gap-2"><x-button href="{{ route('admin.inventory.losses.show', $loss) }}" variant="secondary">View</x-button>@unless($loss->isReversed())<x-button href="{{ route('admin.inventory.losses.reversal.create', $loss) }}" variant="danger">Reverse</x-button>@endunless</div></td></tr>
                    @endforeach
                </tbody>
            </x-responsive-table>
            <x-pagination :paginator="$losses" />
        @endif
    </div>
</x-layouts.app>
