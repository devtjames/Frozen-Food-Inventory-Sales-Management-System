<x-layouts.app title="Products">
    <div class="space-y-6">
        <x-page-heading title="Products" description="Manage product details, selling prices per kilogram, and sales availability.">
            <x-slot:actions><x-button href="{{ route('admin.products.create') }}">Add product</x-button></x-slot:actions>
        </x-page-heading>

        <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-900">Available stock is calculated from remaining batch quantities. Stock is changed only through receiving and auditable adjustments.</div>

        <form method="GET" action="{{ route('admin.products.index') }}" class="card grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-[minmax(15rem,1fr)_minmax(14rem,0.8fr)_12rem_auto] xl:items-end">
            <x-search-field name="search" label="Search products" placeholder="Search name or description" :value="$filters['search'] ?? null" />
            <x-searchable-dropdown name="category" label="Category" :options="$categories" :selected="$filters['category'] ?? null" placeholder="All categories" search-placeholder="Search categories" />
            <x-dropdown name="status" label="Product status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :selected="$filters['status'] ?? null" placeholder="All statuses" />
            <div class="flex gap-2 md:col-span-2 xl:col-span-1">
                <x-button type="submit" class="flex-1 xl:flex-none">Filter</x-button>
                @if(array_filter($filters))<x-button href="{{ route('admin.products.index') }}" variant="secondary" class="flex-1 xl:flex-none">Clear</x-button>@endif
            </div>
        </form>

        @if($products->isEmpty())
            <x-empty-state title="{{ array_filter($filters) ? 'No products match your filters' : 'No products registered yet' }}" description="{{ array_filter($filters) ? 'Try changing the search, category, or status filter.' : 'Register the first product and set its selling price per kilogram.' }}">
                <x-slot:icon><svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 8.5 12 4l8 4.5v8L12 21l-8-4.5z" stroke-linejoin="round"/><path d="m4 8.5 8 4.5 8-4.5M12 13v8" stroke-linejoin="round"/></svg></x-slot:icon>
                @if(! array_filter($filters))
                    <x-slot:action><x-button href="{{ route('admin.products.create') }}">Add product</x-button></x-slot:action>
                @endif
            </x-empty-state>
        @else
            <x-responsive-table label="Registered products">
                <x-slot:mobile>
                    @foreach($products as $product)
                        @php($imageUrl = $productService->imageUrl($product))
                        <article class="p-4">
                            <div class="flex items-start gap-3">
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="size-16 shrink-0 rounded-xl border border-slate-200 object-cover">
                                @else
                                    <span class="grid size-16 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-400" aria-hidden="true"><svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 8.5 12 4l8 4.5v8L12 21l-8-4.5z" stroke-linejoin="round"/><path d="m4 8.5 8 4.5 8-4.5M12 13v8"/></svg></span>
                                @endif
                                <div class="min-w-0 flex-1"><h2 class="font-bold text-slate-950">{{ $product->name }}</h2><p class="mt-0.5 text-sm text-slate-600">{{ $product->category->name }}</p><p class="mt-2 text-lg font-bold text-brand-800">{{ $formatter->money($product->selling_price) }}<span class="text-sm font-medium text-slate-500">/kg</span></p></div>
                                <x-status-badge :variant="$product->is_active ? 'success' : 'danger'">{{ $product->is_active ? 'Active' : 'Inactive' }}</x-status-badge>
                            </div>
                            @unless($product->category->is_active)<p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">Category inactive — unavailable for new sales</p>@endunless
                            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-slate-500">Available stock</dt><dd class="mt-0.5 font-bold text-brand-800">{{ $formatter->kilograms($product->available_quantity) }}</dd></div><div><dt class="text-slate-500">Stock status</dt><dd class="mt-0.5"><x-status-badge :variant="$product->is_low_stock ? 'warning' : 'success'">{{ $product->is_low_stock ? 'Low stock' : 'In stock' }}</x-status-badge></dd></div></dl>
                            <div class="mt-4 flex flex-wrap gap-2"><x-button href="{{ route('admin.products.show', $product) }}" variant="secondary">View</x-button><x-button href="{{ route('admin.products.edit', $product) }}" variant="secondary">Edit</x-button>@include('admin.products._status-action', ['dialogPrefix' => 'mobile'])</div>
                        </article>
                    @endforeach
                </x-slot:mobile>

                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 font-semibold">Product</th><th class="px-5 py-3 font-semibold">Category</th><th class="px-5 py-3 font-semibold">Selling price</th><th class="px-5 py-3 font-semibold">Available</th><th class="px-5 py-3 font-semibold">Stock</th><th class="px-5 py-3 font-semibold">Status</th><th class="px-5 py-3 text-right font-semibold">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach($products as $product)
                        @php($imageUrl = $productService->imageUrl($product))
                        <tr>
                            <td class="px-5 py-4"><div class="flex items-center gap-3">@if($imageUrl)<img src="{{ $imageUrl }}" alt="" class="size-11 shrink-0 rounded-lg border border-slate-200 object-cover">@else<span class="grid size-11 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-400"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 8.5 12 4l8 4.5v8L12 21l-8-4.5z" stroke-linejoin="round"/></svg></span>@endif<div><p class="font-semibold text-slate-900">{{ $product->name }}</p><p class="mt-0.5 line-clamp-1 max-w-xs text-xs text-slate-500">{{ $product->description ?: 'No description provided.' }}</p></div></div></td>
                            <td class="px-5 py-4 text-slate-700">{{ $product->category->name }}@unless($product->category->is_active)<span class="mt-1 block text-xs font-medium text-amber-700">Inactive category</span>@endunless</td>
                            <td class="whitespace-nowrap px-5 py-4 font-bold text-brand-800">{{ $formatter->money($product->selling_price) }}/kg</td>
                            <td class="whitespace-nowrap px-5 py-4 font-bold text-brand-800">{{ $formatter->kilograms($product->available_quantity) }}</td><td class="px-5 py-4"><x-status-badge :variant="$product->is_low_stock ? 'warning' : 'success'">{{ $product->is_low_stock ? 'Low stock' : 'In stock' }}</x-status-badge></td>
                            <td class="px-5 py-4"><x-status-badge :variant="$product->is_active ? 'success' : 'danger'">{{ $product->is_active ? 'Active' : 'Inactive' }}</x-status-badge></td>
                            <td class="px-5 py-4"><div class="flex flex-wrap justify-end gap-2"><x-button href="{{ route('admin.products.show', $product) }}" variant="secondary">View</x-button><x-button href="{{ route('admin.products.edit', $product) }}" variant="secondary">Edit</x-button>@include('admin.products._status-action', ['dialogPrefix' => 'desktop'])</div></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-responsive-table>
            <x-pagination :paginator="$products" />
        @endif
    </div>
</x-layouts.app>
