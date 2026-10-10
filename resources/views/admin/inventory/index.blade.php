<x-layouts.app title="Available stock">
    <div class="space-y-6">
        <x-page-heading title="Available stock" description="Authoritative balances calculated from the remaining quantities in eligible stock batches.">
            <x-slot:actions><x-button href="{{ route('admin.inventory.receipts.create') }}">Receive stock</x-button><x-button href="{{ route('admin.inventory.adjustments.create') }}" variant="secondary">New adjustment</x-button></x-slot:actions>
        </x-page-heading>
        <x-inventory-nav />

        <form method="GET" action="{{ route('admin.inventory.index') }}" class="card grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-[minmax(14rem,1fr)_minmax(13rem,0.7fr)_minmax(11rem,0.55fr)_auto] xl:items-end">
            <x-search-field name="search" label="Search products" placeholder="Search product name" :value="$filters['search'] ?? null" />
            <x-searchable-dropdown name="category" label="Category" :options="$categories" :selected="$filters['category'] ?? null" placeholder="All categories" search-placeholder="Search categories" />
            <x-dropdown name="stock_status" label="Stock status" :options="['low' => 'Low stock', 'in_stock' => 'In stock']" :selected="$filters['stock_status'] ?? null" placeholder="All stock statuses" />
            <div class="flex gap-2"><x-button type="submit" class="flex-1 md:flex-none">Filter</x-button>@if(array_filter($filters))<x-button href="{{ route('admin.inventory.index') }}" variant="secondary" class="flex-1 md:flex-none">Clear</x-button>@endif</div>
        </form>

        @if($products->isEmpty())
            <x-empty-state title="No products found" description="Register a product or change the current filters before receiving stock." />
        @else
            <x-responsive-table label="Available inventory">
                <x-slot:mobile>
                    @foreach($products as $product)
                        <article class="p-4">
                            <div class="flex items-start justify-between gap-3"><div><h2 class="font-bold text-slate-950">{{ $product->name }}</h2><p class="text-sm text-slate-600">{{ $product->category->name }}</p></div><x-status-badge :variant="$product->is_low_stock ? 'warning' : 'success'">{{ $product->is_low_stock ? 'Low stock' : 'In stock' }}</x-status-badge></div>
                            <p class="mt-4 text-2xl font-bold text-brand-800">{{ $formatter->kilograms($product->available_quantity) }}</p>
                            <p class="text-sm text-slate-500">Alert threshold: {{ $formatter->kilograms($product->low_stock_threshold) }}</p>
                            <div class="mt-4 flex flex-wrap gap-2"><x-button href="{{ route('admin.inventory.receipts.create', ['product' => $product->id]) }}">Add stock</x-button><x-button href="{{ route('admin.inventory.adjustments.create', ['product' => $product->id]) }}" variant="secondary">Adjust</x-button><x-button href="{{ route('admin.products.show', $product) }}" variant="ghost">Product</x-button></div>
                        </article>
                    @endforeach
                </x-slot:mobile>
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 font-semibold">Product</th><th class="px-5 py-3 font-semibold">Available</th><th class="px-5 py-3 font-semibold">Threshold</th><th class="px-5 py-3 font-semibold">Stock status</th><th class="px-5 py-3 text-right font-semibold">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-200 bg-white">@foreach($products as $product)<tr><td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $product->name }}</p><p class="text-xs text-slate-500">{{ $product->category->name }}</p></td><td class="px-5 py-4 text-lg font-bold text-brand-800">{{ $formatter->kilograms($product->available_quantity) }}</td><td class="px-5 py-4 text-slate-600">{{ $formatter->kilograms($product->low_stock_threshold) }}</td><td class="px-5 py-4"><x-status-badge :variant="$product->is_low_stock ? 'warning' : 'success'">{{ $product->is_low_stock ? 'Low stock' : 'In stock' }}</x-status-badge></td><td class="px-5 py-4"><div class="flex justify-end gap-2"><x-button href="{{ route('admin.inventory.receipts.create', ['product' => $product->id]) }}">Add stock</x-button><x-button href="{{ route('admin.inventory.adjustments.create', ['product' => $product->id]) }}" variant="secondary">Adjust</x-button></div></td></tr>@endforeach</tbody>
            </x-responsive-table>
            <x-pagination :paginator="$products" />
        @endif
    </div>
</x-layouts.app>
