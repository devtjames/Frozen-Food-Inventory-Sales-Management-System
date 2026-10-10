<x-layouts.app :title="$product->name">
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-heading :title="$product->name" description="Product details and current selling configuration.">
            <x-slot:actions><x-button href="{{ route('admin.products.edit', $product) }}" variant="secondary">Edit product</x-button>@include('admin.products._status-action')</x-slot:actions>
        </x-page-heading>

        <div class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
            <x-responsive-card>
                <div class="grid aspect-square place-items-center overflow-hidden rounded-xl bg-slate-50">
                    @if($imageUrl)<img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="size-full object-contain">@else<svg viewBox="0 0 24 24" class="size-20 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M4 8.5 12 4l8 4.5v8L12 21l-8-4.5z" stroke-linejoin="round"/><path d="m4 8.5 8 4.5 8-4.5M12 13v8"/></svg>@endif
                </div>
            </x-responsive-card>

            <div class="space-y-6">
                <x-responsive-card>
                    <dl class="grid gap-5 sm:grid-cols-2">
                        <div><dt class="text-sm text-slate-500">Category</dt><dd class="mt-1 font-semibold text-slate-900">{{ $product->category->name }}</dd></div>
                        <div><dt class="text-sm text-slate-500">Product status</dt><dd class="mt-1"><x-status-badge :variant="$product->is_active ? 'success' : 'danger'">{{ $product->is_active ? 'Active' : 'Inactive' }}</x-status-badge></dd></div>
                        <div><dt class="text-sm text-slate-500">Selling price per kg</dt><dd class="mt-1 text-xl font-bold text-brand-800">{{ $formatter->money($product->selling_price) }}/kg</dd></div>
                        <div><dt class="text-sm text-slate-500">Low-stock threshold</dt><dd class="mt-1 font-semibold text-slate-900">{{ $formatter->kilograms($product->low_stock_threshold) }}</dd></div>
                        <div><dt class="text-sm text-slate-500">Available for new sales</dt><dd class="mt-1"><x-status-badge :variant="$product->isAvailableForNewSales() ? 'success' : 'warning'">{{ $product->isAvailableForNewSales() ? 'Yes' : 'No' }}</x-status-badge></dd></div>
                        <div><dt class="text-sm text-slate-500">Available stock</dt><dd class="mt-1 text-xl font-bold text-brand-800">{{ $formatter->kilograms($availableQuantity) }}</dd></div>
                        <div><dt class="text-sm text-slate-500">Registered</dt><dd class="mt-1 font-medium text-slate-900">{{ $formatter->businessDate($product->created_at) }}</dd></div>
                        <div><dt class="text-sm text-slate-500">Last updated</dt><dd class="mt-1 font-medium text-slate-900">{{ $formatter->businessDate($product->updated_at) }}</dd></div>
                    </dl>
                </x-responsive-card>
                <x-responsive-card title="Description"><p class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ $product->description ?: 'No description provided.' }}</p></x-responsive-card>
                <div class="flex flex-wrap gap-2"><x-button href="{{ route('admin.inventory.receipts.create', ['product' => $product->id]) }}">Add stock</x-button><x-button href="{{ route('admin.inventory.adjustments.create', ['product' => $product->id]) }}" variant="secondary">Adjust stock</x-button></div>
            </div>
        </div>
    </div>
</x-layouts.app>
